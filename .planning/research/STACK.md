# Technology Stack — POS, Dashboard & Reports Additions

**Project:** Sales Management — Refactor & Completion milestone
**Researched:** 2026-06-21
**Scope:** Libraries to ADD for POS interface, manager dashboard (KPIs + charts), and report exports (CSV/PDF) on top of the existing Laravel 12 + Inertia.js v1/v2 + Vue 3.5 + PrimeVue 4.5.5 + Tailwind 3.4 + Pest 3 stack. The existing stack is documented in `.planning/codebase/STACK.md` and is NOT re-researched here.

**Existing relevant deps already installed (do NOT reinstall):**
- `chart.js` 4.5.1 (npm) — Chart.js core
- `primevue` 4.5.5 — includes `primevue/chart` Chart.js wrapper component
- `moment-timezone` 0.6.1 — date/time formatting (`useDatetimeFormatter`)
- `pinia` 3.0.4 — POS store (`usePosStore`)
- `axios` 1.15.0 — API client (`useApi`)

---

## Recommended Stack (Additions Only)

### 1. Charting — Manager Dashboard

**Recommendation: Use the already-installed PrimeVue `<Chart>` component (Chart.js 4.5.1 wrapper). Do NOT add a new charting library.**

| Aspect | Value |
|--------|-------|
| Library | `primevue/chart` (wraps `chart.js` 4.5.1) — **already installed** |
| Confidence | **HIGH** |
| Install | None — both packages already in `package.json` |
| Versions verified | primevue 4.5.5, chart.js 4.5.1 (installed in `node_modules`, confirmed) |

**Why for THIS codebase:**
- PrimeVue Chart is already used in two existing pages (`Pages/Inventory/Show/Components/PurchasePriceMargin.vue` and `Pages/Batches/Show/Components/QuantityDoughnut.vue`), so the project already has a working chart pattern to mirror. Convention (`.claude/rules/vue-frontend.md`: "PrimeVue components are imported directly from `primevue`") is satisfied — `import Chart from "primevue/chart"`.
- Chart.js 4.x covers every chart type the dashboard needs: `line` (sales trend, last 7/30 days), `doughnut`/`pie` (top products share), `bar` (top products by revenue, transactions per day), stacked bar (sales by store/day). PrimeVue's Chart wrapper passes `type`, `data`, `options`, `plugins`, and `canvasProps` straight through to Chart.js, so no PrimeVue limitation blocks the dashboard. Multi-axis, stacked, and combo charts are all supported per the official PrimeVue 4.5.5 docs.
- Theme consistency: the dashboard must match the app's Aura blue palette (`#00539b`). With Chart.js options we set `backgroundColor`/`borderColor` arrays directly (as `QuantityDoughnut.vue` already does), keeping styling in the same Tailwind/PrimeVue token family. ApexCharts/ECharts would each need a separate theme config.
- Bundle size: Chart.js is already in the bundle. Adding ApexCharts (~450KB min) or ECharts (~1MB min) would roughly double or triple the charting payload for zero UX gain on a 3-chart manager dashboard.

**Alternatives considered and rejected:**

| Library | Version | Why NOT for this codebase |
|---------|---------|---------------------------|
| `vue3-apexcharts` | 1.11.1 (2026-03-03) | Not ApexCharts-official (community wrapper). Adds apexcharts 5.x (~450KB) alongside already-installed Chart.js — two chart engines for one app. No PrimeVue integration. Better tooltips/interactivity, but dashboard needs are basic. |
| `vue-echarts` + `echarts` | 8.0.1 / 6.1.0 (2026-02-18) | Heaviest option (~1MB+ core). Most powerful, but overkill for line/doughnut/bar KPI charts. Wrapper API differs from PrimeVue Chart pattern already in the codebase. Justified only for complex interactive analytics not in scope. |
| `vue-chartjs` | 5.3.3 (2025-11-03) | Official Chart.js Vue 3 wrapper. Would be the pick on a greenfield project, but PrimeVue Chart already wraps the same Chart.js 4.x and is already used here. Adding vue-chartjs would create two wrappers over the same engine. |

**When to revisit:** If a future milestone needs real-time streaming charts, large-dataset heatmaps, or complex brush/zoom interactions, switch that specific chart to ECharts. Not needed now.

---

### 2. PDF Export — Reports

**Recommendation: `barryvdh/laravel-dompdf` v3.1.2 (wraps `dompdf/dompdf` v3.1.5).**

| Aspect | Value |
|--------|-------|
| Library | `barryvdh/laravel-dompdf` |
| Version | ^3.1 (latest v3.1.2) |
| Confidence | **HIGH** |
| Install | `composer require barryvdh/laravel-dompdf:^3.1` |
| Verified | packagist: requires `php ^8.1`, `illuminate/support ^9|^10|^11|^12|^13`, `dompdf/dompdf ^3.0` — Laravel 12 + PHP 8.3 compatible |

**Why for THIS codebase:**
- **Laravel-native integration.** Provides a `Pdf` facade with `loadView()`, `loadHTML()`, `stream()`, `download()`, `response()`. Reports are Blade-rendered (`resources/views/reports/*.blade.php`) then converted — fits the project's server-side-rendered-report model where `ReportController` streams a PDF download. No client-side PDF library needed, which keeps the Vue bundle lean and keeps report logic in services (per the controller-thin / service-owns-logic convention).
- **No external binaries.** dompdf is pure PHP. The project explicitly runs with "No Docker, no CI/CD, no Makefile" and a single PHP-FPM server (`.planning/codebase/STACK.md`). Snappy/wkhtmltopdf and Browsershot both require system binaries (wkhtmltopdf, or headless Chromium + Puppeteer) that would add deployment complexity and a Node/Chrome dependency to a PHP-only production server. dompdf avoids all of that.
- **Adequate CSS for tabular reports.** dompdf v3 supports CSS 2.1 + partial flexbox, `@page` with margins, headers/footers, and repeated table headers (`<thead>`). Sales/inventory/cash-register/purchase reports are dense tables with totals — exactly dompdf's sweet spot. Logos/images use local file paths (spatie/laravel-medialibrary stores locally on the `public` disk).
- **Matches the report read-only constraint.** PROJECT.md: "Reports: Read-only — no write operations. Reuse existing services for aggregation." A Blade view + dompdf keeps reports entirely server-side and read-only, with no new API endpoints or frontend state.

**Alternatives considered and rejected:**

| Library | Version | Why NOT for this codebase |
|---------|---------|---------------------------|
| `dompdf/dompdf` (raw) | 3.1.5 | Underlying engine; `barryvdh/laravel-dompdf` wraps it with the `Pdf` facade and Laravel config/view integration — no reason to use raw. |
| `mpdf` | 8.x | Good Unicode/CJK support, but heavier and less Laravel-idiomatic than barryvdh's wrapper. Reports are Spanish/English (Latin script) — dompdf's Unicode support is sufficient. |
| `laravel-snappy` (knplabs/knp-snappy + wkhtmltopdf) | 1.x | Requires the `wkhtmltopdf` system binary. Adds deployment friction on a PHP-only server. Better CSS (WebKit-based) but not worth the binary dependency for table reports. |
| `spatie/browsershot` | 5.4.0 | Renders via headless Chromium (requires Node + Puppeteer + Chrome installed on the production server). Produces the most accurate PDFs and can render PrimeVue-styled HTML, but the project has no Node runtime in production and `npm run build` only runs at deploy time. Introducing Browsershot means shipping Chrome to prod. Use only if reports must look pixel-identical to PrimeVue UI — out of scope. |

**Caveats to flag in PITFALLS:**
- dompdf does NOT run JavaScript. Report PDFs must be fully server-rendered Blade — do NOT try to render a PrimeVue page to PDF.
- Remote images are blocked by default (`config/dompdf.php` `isRemoteEnabled`). Use local file paths via `public_path()`/`Storage::path()`, or enable remote explicitly with caution.
- Large reports (thousands of rows) can hit memory limits; stream in chunks or paginate (see ARCHITECTURE).

---

### 3. CSV Export — Reports

**Recommendation: `spatie/simple-excel` 3.10.0 for CSV (and optional xlsx), streamed via Laravel `StreamedResponse`.**

| Aspect | Value |
|--------|-------|
| Library | `spatie/simple-excel` |
| Version | ^3.10 (latest 3.10.0) |
| Confidence | **HIGH** |
| Install | `composer require spatie/simple-excel:^3.10` |
| Verified | packagist 3.10.0; wraps `openspout/openspout` (actively maintained successor to box/spout) |

**Why for THIS codebase:**
- **Stream-based, memory-efficient.** `SimpleExcelWriter::create($path)->addRow(...)->close()` writes rows incrementally — ideal for sales/inventory reports that can span thousands of rows without blowing memory. Combined with Laravel's `response()->streamDownload()`, the controller can stream a CSV directly to the browser without materializing the whole file.
- **Spatie ecosystem fit.** The project already depends on three Spatie packages (`spatie/laravel-permission`, `spatie/laravel-activitylog`, `spatie/laravel-medialibrary`). A fourth Spatie package is conventionally consistent and the team is already familiar with Spatie's API style.
- **One API for CSV and xlsx.** If a future milestone needs Excel output, the same `SimpleExcelWriter` API produces `.xlsx` — no second library. For this milestone, CSV satisfies the export requirement.
- **Read-only reports.** `SimpleExcelWriter` is write-only for exports; no risk of accidental mass-import logic leaking in.

**Alternatives considered and rejected:**

| Library | Version | Why NOT for this codebase |
|---------|---------|---------------------------|
| Raw `Laravel StreamedResponse` + `fputcsv` | (built-in) | Zero dependencies but verbose and error-prone (manual header escaping, BOM handling for Excel, encoding). Worth it only to avoid one dependency — the convenience + correctness of simple-excel outweighs that. |
| `league/csv` | 9.28.0 | RFC 4180-compliant, stream filters, very capable. Lower-level than simple-excel — more boilerplate for the same streamed export. Better choice if we needed CSV parsing/import or unusual encodings; we only need export. |

**Pattern to follow (fits existing conventions):**
```php
// In ReportController (final class, authorize via PermissionsEnum, delegate to ReportService)
return response()->streamDownload(
    fn () => $this->reportService->exportSalesCsv($filters),
    "sales-report-{$date}.csv",
    ['Content-Type' => 'text/csv; charset=UTF-8'],
);
```
The service uses `SimpleExcelWriter` and writes to `php://output`.

---

### 4. Barcode / Scan Input — POS

**Recommendation: Native hidden `<input>` + keyboard-wedge handling. Do NOT add a scanner library.**

| Aspect | Value |
|--------|-------|
| Approach | Native focused `<InputText>` (PrimeVue) listening for rapid keystrokes + Enter terminator |
| Library | None (zero dependencies) |
| Confidence | **HIGH** |
| Install | None |

**Why for THIS codebase:**
- **USB/Bluetooth barcode scanners are HID keyboard wedges.** They type the barcode characters quickly and end with `Enter` (or a configurable suffix). A normal PrimeVue `InputText` focused in the POS receives exactly that — typed text + a submit event. No camera decoding, no library. This is the standard POS scan pattern and works with every commodity USB scanner (Zjiang, Honeywell, Symbol, etc.).
- **Matches PrimeVue convention.** The POS uses `PosLayout` + PrimeVue components (`InputText`, `InputNumber`). A scanner library like `quagga2` (camera-based) would introduce a `<video>` stream, WebRTC permissions, and a canvas decoder — all unnecessary when a hardware scanner is present, and a poor fit for a desktop-first POS.
- **No bundle cost.** Zero KB added. The scan handler is ~30 lines of Vue composable logic (`onScanInput`, `onScanEnter`, debounced reset).

**Alternatives considered and rejected:**

| Library | Version | Why NOT for this codebase |
|---------|---------|---------------------------|
| `quagga2` / `@ericblade/quagga2` | 1.x | Camera-based barcode decoding via getUserMedia. Only justified for mobile/device-camera scanning. The POS is web-first on desktop with a hardware scanner (PROJECT.md: "mobile-friendly POS is a future milestone"). Adds ~200KB and a `<video>` element. |
| `vue3-barcode-scanner` / similar | various | Thin wrappers around the same keyboard-wedge pattern — adds a dependency for logic that is trivially a focused input + keydown handler. Most are unmaintained. |

**Fallback / future:** If a future milestone adds tablet/mobile POS without a hardware scanner, add `@ericblade/quagga2` then. Keep the scan-input interface in a composable (`useBarcodeScan`) so the implementation can swap without touching cart logic.

**Implementation note:** Create `resources/js/Composables/useBarcodeScan.ts` that exposes `scanInputRef`, `onScanSubmit`, and a `resetScanInput`. The POS search box auto-focuses on mount and after each successful scan so the cashier can scan repeatedly without clicking.

---

### 5. Receipt Printing — POS

**Recommendation: Hybrid — browser `window.print()` of a receipt-styled HTML component for the MVP, with `mike42/escpos-php` v4.0 available as the server-side path for networked thermal printers.**

| Aspect | Value |
|--------|-------|
| Primary (browser) | `window.print()` + a hidden receipt `<div>` + `@media print` CSS |
| Secondary (server, networked thermal) | `mike42/escpos-php` ^4.0 (v4.0, 2022-05-23) |
| Confidence | **MEDIUM** (browser path HIGH for MVP; escpos path MEDIUM — depends on deployment having a reachable thermal printer) |
| Install | Browser: none. Server: `composer require mike42/escpos-php:^4.0` — only when a networked thermal printer is confirmed in deployment |

**Why this hybrid for THIS codebase:**
- **Browser print works everywhere, zero dependencies.** Render a compact receipt component (PrimeVue-free, plain HTML styled with Tailwind `@media print` rules), call `window.print()`, and let the OS print dialog target the thermal printer (most OSes remember the last-used printer). This satisfies the PROJECT.md "Print receipt" requirement with no new packages and no server-to-printer connectivity. Sufficient for the MVP and for deployments where the printer is USB-attached to the POS client machine.
- **escpos-php for networked thermal printers.** When the thermal printer is Ethernet/IP-reachable from the PHP server (common in multi-store deployments where the server is headless), `mike42/escpos-php` v4.0 gives raw ESC/POS control: text justification, barcode printing, QR codes, cash-drawer pulse (`pulse()`), and paper cut (`cut()`). It supports `NetworkPrintConnector` (IP:port 9100), `FilePrintConnector` (`/dev/usb/lp0`), `WindowsPrintConnector` (SMB), and `CupsPrintConnector`. The library is stable (2.8k stars, last release 2022 but the ESC/POS protocol is frozen); PHP 8.3 compatible (requires `php ^7.3`, `intl`, `json`, `zlib` — all present).
- **Fits the service pattern.** A `ReceiptPrinterService` (final class, `app/Services/`) can abstract both paths: `printViaBrowser()` returns a signed URL or Inertia-rendered receipt view; `printViaEscpos($shift, $order)` opens a `NetworkPrintConnector` and builds the receipt. The POS controller delegates to the service, keeping controllers thin per convention.
- **Cash drawer kick.** escpos-php `pulse()` opens an Epson-standard cash drawer connected to the printer's DK port — a common POS requirement. Browser print cannot trigger the drawer directly.

**Alternatives considered and rejected:**

| Approach | Why NOT (alone) |
|----------|----------------|
| Browser print only | Cannot trigger cash-drawer kick or do automatic paper cut; depends on the OS print dialog and user not changing settings. Fine for MVP, insufficient for unattended multi-store. |
| escpos-php only | Requires the PHP server to reach the printer over the network. USB-attached printers on the client machine aren't reachable from a remote PHP server. Not all deployments have networked thermal printers. |
| `rawprint` / JS ESC/POS libs | Browser-to-USB ESC/POS requires WebUSB (Chrome-only, permissions, fragile). Not reliable across the POS client's browser. |

**Decision rule for the roadmap:** Implement browser print first (covers the PROJECT.md requirement). Add escpos-php as a follow-up task gated on "deployment has a networked thermal printer" — track in PITFALLS so the phase plan can flag it.

---

### 6. Number / Currency Formatting — Receipts & Reports

**Recommendation: Keep the existing `useCurrencyFormatter` composable for the frontend; add a small backend `MoneyFormatter` helper backed by PHP's `NumberFormatter` (intl extension). Do NOT add `brick/money` or `numeral.js`.**

| Aspect | Value |
|--------|-------|
| Frontend | Existing `useCurrencyFormatter` composable (settings-driven `currency` + `decimal_precision`) — already used everywhere |
| Backend | New `App\Support\MoneyFormatter` using PHP `NumberFormatter` (intl, already required by escpos-php and present in PHP 8.3) |
| Libraries | None added |
| Confidence | **HIGH** |

**Why for THIS codebase:**
- **The frontend composable already works.** `useCurrencyFormatter` reads `finance.currency` and `finance.decimal_precision` from settings and formats consistently. It is used across every admin page that shows money. The POS cart, payment display, and on-screen receipt preview should reuse it — no new frontend dependency.
- **One caveat to fix:** the current composable's signature is `formatCurrency(value: string)` — it calls `parseFloat(value).toFixed(...)`. POS cart math will pass numbers, not strings. A small widening of the param type (`value: string | number`) is needed during POS implementation (tracked in CONCERNS-style fix list). This is a 1-line change, not a reason to swap libraries.
- **Backend formatting needs a backend solution for PDFs.** dompdf renders Blade server-side, so `useCurrencyFormatter` (a Vue composable) is unavailable there. PHP's built-in `NumberFormatter::formatCurrency()` (intl extension) produces locale-correct currency strings (e.g., "Bs 1.250,00" for es-BO, "BOB 1,250.00" for en) using the same `finance.currency` setting. A thin `MoneyFormatter` wrapper in `app/Support/` keeps report formatting consistent with the frontend without adding a money-math library.
- **brick/money is the wrong fit here.** `brick/money` 0.13.0 is excellent for money math (allocation, rounding modes, currency conversion), but this codebase stores money as `decimal:2` columns and computes totals with `(float)` + `round()` in `SalesOrderService::calculateTotals()` (verified). Adopting brick/money would require refactoring the financial core (SalesOrder, PurchaseOrder, CashRegisterShift services) — explicitly out of scope for this milestone and a source of regression risk. We only need display formatting, not money math.
- **numeral.js is unnecessary.** `numeral.js` is a frontend number formatter, but `useCurrencyFormatter` + native `Intl.NumberFormat` already cover it. Adding numeral.js would duplicate existing capability and add ~40KB.

**When to reconsider brick/money:** If a future milestone introduces multi-currency sales, tax-inclusive pricing across currencies, or profit allocation, adopt `brick/money` then and refactor the financial core with a dedicated phase. Not this milestone.

---

### 7. Report Endpoint Pattern — Laravel/Inertia-Specific

**Recommendation: Web routes render an Inertia report page with filters; export actions are separate web routes returning streamed downloads (`StreamedResponse` for CSV, `Response->download()` for PDF). Do NOT use Inertia for the file download itself.**

| Aspect | Value |
|--------|-------|
| Pattern | Inertia-rendered filter page + separate `routes/web.php` export endpoints returning binary downloads |
| Confidence | **HIGH** (matches existing conventions) |
| Install | None |

**Why for THIS codebase:**
- **Inertia is for page navigation, not file downloads.** Inertia intercepts responses expecting JSON (the Inertia protocol). A CSV/PDF binary response must bypass Inertia — either by hitting a non-Inertia route (regular GET that returns a `StreamedResponse`/`BinaryFileResponse`) or by triggering the request outside Inertia's axios client. The cleanest pattern, matching the existing resource-style route naming (`.claude/rules/routes-and-api.md`), is:
  ```php
  Route::get('reports/sales', [ReportController::class, 'index'])->name('reports.sales');
  Route::get('reports/sales/export', [ReportController::class, 'exportSales'])->name('reports.sales.export');
  ```
  The `index` action renders `Inertia::render('Reports/Sales/Index', $data)`; the `exportSales` action returns `response()->streamDownload(...)` (CSV) or `Pdf::loadView(...)->download(...)` (PDF). Same filters are passed as query params.
- **Frontend triggers a normal browser navigation for exports.** Use a plain `<a :href="route('reports.sales.export', filters)" download>` link or `window.location.href = route(...)`. Do NOT use Inertia `router.visit()` for the download — Inertia would try to parse the binary as JSON and error. This is the documented Inertia pattern for file downloads.
- **Authorization via PermissionsEnum.** Both `index` and `exportSales` call `$this->authorize(PermissionsEnum::REPORTS_VIEW)` (new permission case to add). Form Request validation for filters (`app/Http/Requests/Reports/SalesReportRequest`) with date-range + store + user rules.
- **Services own aggregation.** `ReportService` (final, `app/Services/`) methods like `salesReport(array $filters): array` return view data; `exportSalesCsv(array $filters)` writes to a stream; `exportSalesPdf(array $filters): string` returns PDF bytes via dompdf. No business logic in controllers, matching `.claude/rules/laravel-backend.md`.
- **No new API endpoints.** PROJECT.md constraint: "Only the ~10 endpoints actually used by Inertia pages may remain after refactor. New dynamic fetches should prefer Inertia partial-reload / deferred props over new API endpoints." Reports are server-rendered; filters re-trigger `router.visit(route('reports.sales'), { data: filters, preserveState: true })` — an Inertia partial reload, not an API call.

**Anti-pattern to avoid (flagged in PITFALLS):**
- Do NOT generate CSV/PDF on the client (e.g., `jspdf`, `papaparse`). It duplicates server-side aggregation logic in JS, bloats the bundle, and breaks the read-only-reports constraint by requiring the full dataset to be shipped to the browser. All export generation stays server-side.

---

## Summary Table

| Need | Recommendation | Version | New dep? | Confidence |
|------|----------------|---------|----------|------------|
| Dashboard charts | PrimeVue `<Chart>` + Chart.js 4.5.1 | 4.5.5 / 4.5.1 | No (installed) | HIGH |
| PDF export | `barryvdh/laravel-dompdf` | ^3.1 | Yes (composer) | HIGH |
| CSV export | `spatie/simple-excel` | ^3.10 | Yes (composer) | HIGH |
| Barcode scan input | Native focused input + keyboard-wedge | — | No | HIGH |
| Receipt print (MVP) | `window.print()` + `@media print` CSS | — | No | HIGH |
| Receipt print (networked thermal) | `mike42/escpos-php` | ^4.0 | Yes (composer, gated) | MEDIUM |
| Currency/number format (FE) | Existing `useCurrencyFormatter` | — | No | HIGH |
| Currency/number format (BE) | `App\Support\MoneyFormatter` + PHP `NumberFormatter` (intl) | — | No | HIGH |
| Report endpoint pattern | Inertia render + separate streamed-download web routes | — | No | HIGH |

## Installation

```bash
# Backend — PDF + CSV export (required for Reports phase)
composer require barryvdh/laravel-dompdf:^3.1 spatie/simple-excel:^3.10

# Backend — ESC/POS thermal printing (ONLY when networked thermal printer confirmed in deployment)
composer require mike42/escpos-php:^4.0

# Frontend — no new npm packages required for POS, Dashboard, or Reports
# (chart.js 4.5.1 + primevue 4.5.5 already installed)
```

After installing `barryvdh/laravel-dompdf`, publish its config (optional, for tuning paper size/orientation and remote-image setting):
```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider" --no-interaction
```

## What NOT to Add (and Why)

| Library | Why not |
|---------|---------|
| `vue3-apexcharts` / `apexcharts` | Duplicates Chart.js already in the bundle; PrimeVue Chart covers all dashboard chart types. |
| `echarts` + `vue-echarts` | ~1MB bundle for 3 simple charts; overkill. |
| `vue-chartjs` | Second wrapper over the same Chart.js engine PrimeVue already wraps. |
| `laravel-snappy` / `wkhtmltopdf` | System binary dependency on a PHP-only server. |
| `spatie/browsershot` | Requires headless Chromium + Node in production. |
| `quagga2` / `@ericblade/quagga2` | Camera-based; POS uses a hardware HID scanner. |
| `brick/money` | Money-math library; codebase uses `decimal:2` + `round()`. Refactoring the financial core is out of scope. |
| `numeral.js` | Duplicates `useCurrencyFormatter` + native `Intl.NumberFormat`. |
| `jspdf` / `papaparse` (client-side export) | Violates read-only-reports + server-side-logic conventions; ships full dataset to browser. |
| `mpdf` | Heavier than dompdf; no Latin-script advantage for es/en reports. |
| `league/csv` | Lower-level than needed; `spatie/simple-excel` covers CSV + future xlsx with one API. |

## Sources

- npm registry (authoritative, accessed 2026-06-21): `chart.js` 4.5.1, `primevue` 4.5.5, `vue3-apexcharts` 1.11.1, `vue-chartjs` 5.3.3, `echarts` 6.1.0, `vue-echarts` 8.0.1, `apexcharts` 5.15.2 — **MEDIUM** (registry versions, HIGH for version facts)
- packagist (authoritative, accessed 2026-06-21): `barryvdh/laravel-dompdf` v3.1.2 (requires `php ^8.1`, `illuminate/support ^9|^10|^11|^12|^13`, `dompdf ^3.0`), `dompdf/dompdf` v3.1.5, `spatie/simple-excel` 3.10.0, `league/csv` 9.28.0, `brick/money` 0.13.0, `mike42/escpos-php` v4.0 (2022-05-23), `spatie/browsershot` 5.4.0 — **MEDIUM** (registry)
- PrimeVue 4.5.5 Chart component docs (primevue.org/chart/, fetched 2026-06-21): confirms Chart.js 4.x wrapper, supported types (pie/doughnut/line/bar/radar/polarArea), props (type/data/options/plugins/canvasProps), multi-axis/stacked/combo support, accessibility via canvasProps — **HIGH** (official docs + verified against installed version)
- mike42/escpos-php GitHub repo (fetched 2026-06-21): v4.0 release, PHP 7.3+/intl/json/zlib requirements, PrintConnector types (Network/File/WindowsPrint/Cups), methods (text/cut/barcode/qrCode/pulse/graphics), printer compatibility list — **MEDIUM** (official repo, v4.0 dated 2022-05-23)
- Codebase inspection (authoritative for this project): `package.json`, `composer.json`, `node_modules/primevue/package.json`, `node_modules/chart.js/package.json`, `resources/js/Pages/Inventory/Show/Components/PurchasePriceMargin.vue`, `resources/js/Pages/Batches/Show/Components/QuantityDoughnut.vue`, `resources/js/Composables/useCurrencyFormatter.ts`, `resources/js/Composables/usePosStore.ts`, `app/Services/SalesOrderService.php` (tax/total calc), `.planning/codebase/STACK.md`, `.planning/codebase/CONVENTIONS.md`, `.planning/PROJECT.md` — **HIGH**

---

*Researched: 2026-06-21*