/**
 * Ambient declarations for the experimental Barcode Detection API.
 * Supported in Chromium-based browsers and Safari 17+; not available in Firefox.
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Barcode_Detection_API
 */

interface BarcodeDetectorOptions {
  formats?: string[];
}

interface DetectedBarcode {
  rawValue: string;
  format: string;
  boundingBox: DOMRectReadOnly;
  cornerPoints: ReadonlyArray<{ x: number; y: number }>;
}

declare class BarcodeDetector {
  constructor(options?: BarcodeDetectorOptions);
  static getSupportedFormats(): Promise<string[]>;
  detect(source: CanvasImageSource): Promise<DetectedBarcode[]>;
}