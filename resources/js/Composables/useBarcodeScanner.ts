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
const REARM_AFTER_ABSENT_MS = 1500;
const RESCAN_COOLDOWN_MS = 1500;

type DetectedCodeHandler = (code: string) => void | Promise<void>;

interface LatchedCode {
  lastSeenAt: number;
  processedAt: number | null;
}

/** Camera scanning requires the BarcodeDetector API and a secure (HTTPS/localhost) context. */
export function isScannerSupported(): boolean {
  return (
    typeof window !== "undefined" &&
    typeof navigator !== "undefined" &&
    navigator.mediaDevices !== undefined &&
    "BarcodeDetector" in window &&
    window.isSecureContext
  );
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
 * `onDetected` for each decoded code. A detected code remains latched until
 * it stays absent long enough to prove it has left the frame, preventing one
 * camera presentation from adding the same item repeatedly.
 */
export function useBarcodeScanner(onDetected: DetectedCodeHandler) {
  let stream: MediaStream | null = null;
  let detector: BarcodeDetector | null = null;
  let videoElement: HTMLVideoElement | null = null;
  let intervalId: ReturnType<typeof setInterval> | null = null;
  let sessionId = 0;
  let detectingSessionId: number | null = null;
  let processingSessionId: number | null = null;
  const queuedCodes: string[] = [];
  const latchedCodes = new Map<string, LatchedCode>();

  function isActiveSession(id: number): boolean {
    return id === sessionId;
  }

  function stopStream(value: MediaStream | null): void {
    value?.getTracks().forEach((track) => track.stop());
  }

  function releaseResources(): void {
    if (intervalId !== null) {
      clearInterval(intervalId);
      intervalId = null;
    }

    stopStream(stream);
    stream = null;
    detector = null;
    videoElement?.pause();
    if (videoElement !== null) videoElement.srcObject = null;
    videoElement = null;
    detectingSessionId = null;
    processingSessionId = null;
    queuedCodes.length = 0;
    latchedCodes.clear();
  }

  async function createDetector(): Promise<BarcodeDetector | null> {
    try {
      const supported = await BarcodeDetector.getSupportedFormats();
      const formats = WANTED_FORMATS.filter((format) => supported.includes(format));
      if (formats.length === 0) return null;

      return new BarcodeDetector({ formats: [...formats] });
    } catch {
      try {
        return new BarcodeDetector();
      } catch {
        return null;
      }
    }
  }

  function rearmAbsentCodes(codes: Set<string>, now: number): void {
    for (const [code, latch] of latchedCodes) {
      if (codes.has(code)) {
        latch.lastSeenAt = now;
        continue;
      }

      if (latch.processedAt !== null && now - latch.lastSeenAt >= REARM_AFTER_ABSENT_MS && now - latch.processedAt >= RESCAN_COOLDOWN_MS) {
        latchedCodes.delete(code);
      }
    }
  }

  function processQueuedCodes(currentSessionId: number): void {
    if (!isActiveSession(currentSessionId) || processingSessionId === currentSessionId) return;

    const code = queuedCodes.shift();
    if (code === undefined) return;

    processingSessionId = currentSessionId;
    void (async () => {
      try {
        await onDetected(code);
      } catch {
        // The parent handles lookup errors; continue processing subsequent scans.
      } finally {
        if (!isActiveSession(currentSessionId)) return;

        const latch = latchedCodes.get(code);
        if (latch !== undefined) latch.processedAt = Date.now();
        processingSessionId = null;
        processQueuedCodes(currentSessionId);
      }
    })();
  }

  async function tick(video: HTMLVideoElement, currentSessionId: number): Promise<void> {
    if (!isActiveSession(currentSessionId) || detectingSessionId === currentSessionId || detector === null || video.readyState < 2) return;

    const activeDetector = detector;
    detectingSessionId = currentSessionId;
    try {
      const detectedCodes = await activeDetector.detect(video);
      if (!isActiveSession(currentSessionId) || detector !== activeDetector) return;

      const codes = new Set(detectedCodes.map((entry) => entry.rawValue.trim()).filter((value) => value.length > 0));
      const now = Date.now();
      rearmAbsentCodes(codes, now);

      const newCodes = [...codes].filter((value) => !latchedCodes.has(value));
      if (newCodes.length === 0) return;

      for (const code of newCodes) {
        latchedCodes.set(code, { lastSeenAt: now, processedAt: null });
        queuedCodes.push(code);
      }

      processQueuedCodes(currentSessionId);
    } catch {
      // Transient decode failure on a partial frame; the next tick retries.
    } finally {
      if (detectingSessionId === currentSessionId) detectingSessionId = null;
    }
  }

  /** Opens the camera and starts the detection loop. Returns an error type, or null on success. */
  async function start(video: HTMLVideoElement): Promise<ScannerErrorType | null> {
    stop();
    const currentSessionId = ++sessionId;

    if (typeof navigator === "undefined" || navigator.mediaDevices === undefined) return "unsupported";
    if (!("BarcodeDetector" in window)) return "unsupported";
    if (!window.isSecureContext) return "insecure-context";

    const acquired = await acquireStream();
    if (!isActiveSession(currentSessionId)) {
      stopStream(acquired.stream);
      return null;
    }

    if (acquired.stream === null) return acquired.error ?? "unknown";

    stream = acquired.stream;
    const createdDetector = await createDetector();
    if (!isActiveSession(currentSessionId)) {
      stopStream(acquired.stream);
      return null;
    }

    if (createdDetector === null) {
      stop();
      return "unsupported";
    }

    detector = createdDetector;
    videoElement = video;
    video.srcObject = stream;
    try {
      await video.play();
    } catch {
      if (!isActiveSession(currentSessionId)) return null;

      stop();
      return "unknown";
    }

    if (!isActiveSession(currentSessionId)) return null;

    intervalId = setInterval(() => {
      void tick(video, currentSessionId);
    }, DETECTION_INTERVAL_MS);

    return null;
  }

  /** Stops the detection loop and releases the camera. Safe to call multiple times. */
  function stop(): void {
    sessionId++;
    releaseResources();
  }

  onScopeDispose(stop);

  return { start, stop };
}
