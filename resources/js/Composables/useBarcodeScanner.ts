import { onScopeDispose } from "vue";

export type ScannerErrorType = "unsupported" | "insecure-context" | "permission-denied" | "no-camera" | "unknown";

const WANTED_FORMATS = [
  "qr_code",
  "ean_13",
  "ean_8",
  "upc_a",
  "upc_e",
  "code_128",
  "code_39",
  "code_93",
  "itf",
  "codabar",
  "data_matrix",
] as const;

const DETECTION_INTERVAL_MS = 200;
const DUPLICATE_SCAN_COOLDOWN_MS = 1500;

/** Camera scanning requires the BarcodeDetector API and a secure (HTTPS/localhost) context. */
export function isScannerSupported(): boolean {
  return typeof window !== "undefined" && "BarcodeDetector" in window && window.isSecureContext;
}

async function acquireStream(): Promise<{ stream: MediaStream | null; error: ScannerErrorType | null }> {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
    return { stream, error: null };
  } catch (error) {
    if (!(error instanceof DOMException)) return { stream: null, error: "unknown" };
    if (error.name === "NotAllowedError" || error.name === "SecurityError") return { stream: null, error: "permission-denied" };
    if (error.name === "NotFoundError" || error.name === "OverconstrainedError") {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        return { stream, error: null };
      } catch (retryError) {
        if (retryError instanceof DOMException && (retryError.name === "NotAllowedError" || retryError.name === "SecurityError")) {
          return { stream: null, error: "permission-denied" };
        }
        return { stream: null, error: "no-camera" };
      }
    }
    return { stream: null, error: "unknown" };
  }
}

/**
 * Camera-based barcode/QR scanning using the native BarcodeDetector API.
 * Runs a continuous detection loop over the live video feed and calls
 * `onDetected` for each decoded code, ignoring duplicate scans within a
 * short cooldown so a single scan gesture cannot add twice.
 */
export function useBarcodeScanner(onDetected: (code: string) => void) {
  let stream: MediaStream | null = null;
  let detector: BarcodeDetector | null = null;
  let intervalId: ReturnType<typeof setInterval> | null = null;
  let starting = false;
  let detecting = false;
  let lastCode: string | null = null;
  let lastCodeAt = 0;

  async function createDetector(): Promise<BarcodeDetector | null> {
    try {
      const supported = await BarcodeDetector.getSupportedFormats();
      const formats = WANTED_FORMATS.filter((format) => supported.includes(format));
      return new BarcodeDetector(formats.length > 0 ? { formats: [...formats] } : undefined);
    } catch {
      try {
        return new BarcodeDetector();
      } catch {
        return null;
      }
    }
  }

  async function tick(video: HTMLVideoElement): Promise<void> {
    if (detecting || detector === null || video.readyState < 2) return;

    detecting = true;
    try {
      const codes = await detector.detect(video);
      const code = codes.find((entry) => entry.rawValue.trim().length > 0);
      if (code === undefined) return;

      const value = code.rawValue.trim();
      const now = Date.now();
      if (value === lastCode && now - lastCodeAt < DUPLICATE_SCAN_COOLDOWN_MS) return;

      lastCode = value;
      lastCodeAt = now;
      onDetected(value);
    } catch {
      // Transient decode failure on a partial frame; the next tick retries.
    } finally {
      detecting = false;
    }
  }

  /** Opens the camera and starts the detection loop. Returns an error type, or null on success. */
  async function start(video: HTMLVideoElement): Promise<ScannerErrorType | null> {
    if (starting) return null;
    stop();

    if (typeof navigator === "undefined" || navigator.mediaDevices === undefined) return "unsupported";
    if (!("BarcodeDetector" in window)) return "unsupported";
    if (!window.isSecureContext) return "insecure-context";

    starting = true;
    const acquired = await acquireStream();
    stream = acquired.stream;
    const acquireError = acquired.error;

    if (stream === null) {
      starting = false;
      return acquireError ?? "unknown";
    }

    detector = await createDetector();
    if (detector === null) {
      stop();
      starting = false;
      return "unsupported";
    }

    video.srcObject = stream;
    try {
      await video.play();
    } catch {
      // Autoplay policies occasionally reject play(); muted+playsinline keeps frames flowing for detection.
    }

    intervalId = setInterval(() => {
      void tick(video);
    }, DETECTION_INTERVAL_MS);
    starting = false;
    return null;
  }

  /** Stops the detection loop and releases the camera. Safe to call multiple times. */
  function stop(): void {
    if (intervalId !== null) {
      clearInterval(intervalId);
      intervalId = null;
    }
    stream?.getTracks().forEach((track) => track.stop());
    stream = null;
    detector = null;
    detecting = false;
    lastCode = null;
    lastCodeAt = 0;
  }

  onScopeDispose(stop);

  return { start, stop };
}