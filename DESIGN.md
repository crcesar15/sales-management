---
name: Sales Management System
description: Operational retail tool — admin dashboard and POS, instrument-grade blue on PrimeVue Aura.
colors:
  operational-navy: "#00539b"
  trust-blue-600: "#003d74"
  trust-blue-700: "#002d57"
  instrument-50: "#edf3fa"
  instrument-100: "#cddcee"
  instrument-200: "#92b8da"
  instrument-300: "#4f8dbc"
  instrument-400: "#156fa8"
  instrument-950: "#000c19"
  surface-ground-light: "#f1f5f9"
  surface-ground-dark: "#020617"
  surface-card-light: "#ffffff"
  surface-card-dark: "#1e293b"
  ink-light: "#1e293b"
  ink-dark: "#f8fafc"
  muted-light: "#64748b"
  muted-dark: "#94a3b8"
  border-light: "#e2e8f0"
  border-dark: "#334155"
  status-success: "#22c55e"
  status-warn: "#f59e0b"
  status-danger: "#ef4444"
  status-warn-border-dark: "#b45309"
  status-danger-border-dark: "#b91c1c"
  noir-ink: "#18181b"
  noir-paper: "#fafafa"
typography:
  display:
    fontFamily: "Lato, system-ui, sans-serif"
    fontSize: "2.5rem"
    fontWeight: 700
    lineHeight: 1.5
  headline:
    fontFamily: "Lato, system-ui, sans-serif"
    fontSize: "2rem"
    fontWeight: 700
    lineHeight: 1.5
  title:
    fontFamily: "Lato, system-ui, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 700
    lineHeight: 1.5
  body:
    fontFamily: "Lato, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Lato, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 500
    letterSpacing: "normal"
rounded:
  control: "6px"
  card: "8px"
  pill: "9999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "32px"
components:
  button-primary:
    backgroundColor: "{colors.operational-navy}"
    textColor: "#ffffff"
    rounded: "{rounded.control}"
    padding: "10px 18px"
  button-primary-hover:
    backgroundColor: "{colors.trust-blue-600}"
  button-primary-active:
    backgroundColor: "{colors.trust-blue-700}"
  button-secondary:
    backgroundColor: "transparent"
    textColor: "{colors.ink-light}"
    rounded: "{rounded.control}"
  button-icon-row:
    backgroundColor: "transparent"
    textColor: "{colors.muted-light}"
    rounded: "{rounded.pill}"
    size: "large"
  card-surface:
    backgroundColor: "{colors.surface-card-light}"
    rounded: "{rounded.card}"
    padding: "16px"
  input-search:
    backgroundColor: "{colors.surface-card-light}"
    textColor: "{colors.ink-light}"
    rounded: "{rounded.control}"
  nav-sidebar-item:
    backgroundColor: "transparent"
    textColor: "{colors.ink-light}"
    rounded: "{rounded.control}"
    padding: "10px 14px"
  nav-sidebar-item-active:
    backgroundColor: "{colors.instrument-50}"
    textColor: "{colors.trust-blue-700}"
---

# Design System: Sales Management System

## 1. Overview

**Creative North Star: "The Shop Floor Instrument"**

This is the instrument panel of a well-run shop. Each reading is precise, each control has a clear next action, and nothing on screen is decorative. The operator — admin in the back office, sales rep at the counter — should feel they are looking at the truth of the operation, not a presentation about it. Every visual decision answers the question: does this help the next action happen faster, or does it compete with the data?

The system is built on PrimeVue 4 with a custom Aura preset; the library owns component-level styling (buttons, inputs, tables, dialogs) and this spec does not re-spec what PrimeVue already commits. What this spec owns is the brand layer on top: the custom Operational Navy primary ramp, the surface-token bridge into Tailwind, the two-register divergence between admin density and POS speed, the reduced-motion budget, and the anti-references that keep the tool from drifting toward consumer-app or legacy-POS territory. The login surface is a separate Vue application with its own Noir (zinc-based) theme and is documented here as a named exception, not a second system.

**Key Characteristics:**

- Instrument-grade, not decorative: data and status carry the interface; chrome recedes.
- Two registers share tokens, diverge in layout: admin (AppLayout, dense, sidebar) and POS (PosLayout, large hit targets, motion-reduced, tablet-first, hard 768px floor).
- One primary color, used sparingly: Operational Navy `#00539b` appears on primary actions, active state, and key status — never as background wash or atmosphere.
- Light and dark themes are first-class; dark mode is class-triggered via `.app-dark` (dual trigger with PrimeVue's class selector).
- Motion is a budget, not a feature: state transitions only, instant on POS-critical paths, `prefers-reduced-motion` respected everywhere.

## 2. Colors: The Operational Navy Palette

One saturated hue carries the brand; everything else is neutral surface. The palette is restrained by doctrine — accent ≤10% of any screen — because rarity is what makes the primary read as "the next action" rather than "decoration."

### Primary

- **Operational Navy** (`#00539b`): the brand anchor. Used on primary buttons, active sidebar item, focused input ring, key status indicators, and the POS skip-link. Never used as a section background, a hero wash, or a gradient stop. In dark mode the lighter `Instrument 400` (`#156fa8`) carries the same role for contrast.
- **Trust Blue 600 / 700** (`#003d74` / `#002d57`): hover and active states for primary controls. The ramp darkens, never lightens, on interaction — primary controls feel like they depress, not glow.
- **Instrument 50** (`#edf3fa`): the only permitted tinted background for primary. Used as the active-route sidebar fill and the highlight background in light mode. Sparingly.

The full ramp (50 → 950) is defined in `resources/js/app.ts` as the custom Aura `primary` semantic and is normative; do not introduce off-ramp blues.

### Neutral

- **Surface Ground Light** (`#f1f5f9`, PrimeVue `surface-100`) / **Surface Ground Dark** (`#020617`, `surface-950`): the page background. Never pure white in light mode — the slight cool tint separates ground from cards.
- **Surface Card Light** (`#ffffff`) / **Surface Card Dark** (`#1e293b`, `surface-800`): cards, dialogs, popover surfaces, sidebar.
- **Ink Light** (`#1e293b`) / **Ink Dark** (`#f8fafc`): body text. Hits ≥7:1 in both themes.
- **Muted Light** (`#64748b`) / **Muted Dark** (`#94a3b8`): secondary text, captions, empty-state copy. Meets 4.5:1 against both card and ground — do not soften further for "elegance."
- **Border Light** (`#e2e8f0`) / **Border Dark** (`#334155`): dividers, table row separators, card outlines. 1px only.

### Status (semantic, never the sole carrier of state)

- **Success** (`#22c55e`), **Warn** (`#f59e0b`), **Danger** (`#ef4444`): PrimeVue severity colors. Always paired with a label or icon — color is confirmation, not the message.
  - **Dark-mode border tonal extensions:** `#b45309` (amber-700) for warn borders and `#b91c1c` (red-700) for danger borders in `.app-dark`, one step darker on the same hue ramp to keep the border visible against `surface-800`. These are documented tonal extensions of `status-warn` / `status-danger`, not off-ramp colors.

### Login exception: Noir

The login app (`resources/js/login/index.js`) uses a separate Noir preset: a zinc-based primary ramp (`#18181b` ink, `#fafafa` paper). It is a deliberate tonal contrast to the operational blue — the door to the instrument is calm and monochrome; inside, the instrument speaks in blue. Do not mix Noir tokens into the app surface or vice versa.

### Named Rules

**The One Instrument Rule.** Operational Navy appears on primary actions, active state, and key status only — never as atmosphere. If the primary color covers more than ~10% of a screen, the screen is shouting, not designing.

**The No-Wash Rule.** Never use Operational Navy (or any primary ramp step) as a full-bleed section background, a gradient, or a decorative tint. The only permitted tinted background is Instrument 50 for the active sidebar route.

**The Status-Pairs Rule.** Color is never the sole carrier of state. Every status indicator pairs its color with a label, icon, or shape so it survives color-blindness and grayscale printing.

## 3. Typography

**Display Font:** Lato (loaded from `cdnfonts`, fallback `system-ui, sans-serif`)
**Body Font:** Lato (same stack)
**Label/Mono Font:** Lato — there is no separate mono family; the system uses one family in multiple weights.

**Character:** A single humanist sans across the whole system. The contrast axis is weight and size, not family pairing — instrument panels don't switch typefaces between readings. Lato's slightly warm geometry keeps the tool from feeling clinical-cold without crossing into consumer-friendly.

### Hierarchy

- **Display** (700, 2.5rem / 40px, line-height 1.5): page titles on admin list pages. Used once per screen.
- **Headline** (700, 2rem / 32px, line-height 1.5): reserved for the dashboard and section-level headings.
- **Title** (700, 1.5rem / 24px, line-height 1.5): card titles, POS section headings.
- **Body** (400, 1rem / 16px, line-height 1.5): all default text, table cells, form labels. Cap line length at 65–75ch in prose contexts; tables and forms are exempt.
- **Label** (500, 0.875rem / 14px): filter labels, table column headers, small captions. Not uppercase, not tracked — instrument labels read like labels, not kickers.

Mobile font scaling: `html { font-size: 12px }` under 768px (see `resources/css/app.css`). The scale compresses, the hierarchy ratio is preserved.

### Named Rules

**The One-Family Rule.** Lato only. Never introduce a second family for "emphasis" or "editorial" feel — emphasis is weight and size, not a different typeface. A serif or display sans on this surface is a tell.

**The No-Kicker Rule.** Do not add tiny uppercase tracked eyebrows above sections. Labels are sentence-case at 14px/500. The 2023-era all-caps kicker is an AI scaffold and reads as decoration on an instrument.

## 4. Elevation

The system is flat by default. Depth is conveyed by surface tonal layering (ground → card → overlay) following PrimeVue's surface tokens, not by shadow. Shadows appear only as a response to state — popover/overlay lift, modal backdrop — never as ambient decoration on resting cards.

### Shadow Vocabulary

- **Overlay lift** (PrimeVue `--p-overlay-popover-background` + default overlay shadow): popovers, dropdowns, dialogs. The only structural shadow in the system.
- **Resting cards**: no shadow. Cards are distinguished from ground by the surface-card background and a 1px border (`border-light` / `border-dark`).

### Named Rules

**The Flat-By-Default Rule.** Resting surfaces are flat. A shadow on a resting card is a bug, not a design choice. Shadows appear only when an element is elevated above the surface (overlay, dialog, popover) and disappear the moment it settles.

**The Tonal-Layering Rule.** When you need to separate two surfaces, change the surface token (ground → card → overlay) before reaching for a border, and reach for a border before reaching for a shadow.

## 5. Components

Component-level styling is owned by PrimeVue 4 (Aura preset). This section documents the brand layer on top — the variant choices, padding, and state treatments that are not PrimeVue defaults — plus the two signature layout shells. When a PrimeVue default serves, use it; do not override for the sake of override.

### Buttons

- **Shape:** Aura control radius (`6px`). Primary action buttons use the `raised` attribute.
- **Primary:** Operational Navy fill, white text, `10px 18px` padding, `uppercase` class on the primary page action (e.g. "Add Brand"). Hover darkens to Trust Blue 600, active to Trust Blue 700 — controls depress, never glow.
- **Secondary / Outlined:** transparent ground, ink text, outlined severity. Used for filter toggles and secondary actions. Switches to primary severity when a filter is active (the badge count reinforces).
- **Icon row actions:** `text` + `rounded` + `size="large"`, muted icon color. Edit / delete / restore actions in table rows. Tactile but restrained — clear hit target, no chrome.
- **Focus:** PrimeVue focus ring (Operational Navy). Never remove.
- **States carry the doctrine:** hover darkens, active darkens further, focus rings stay. No scale transforms, no glow, no shadow bloom on buttons.

### Inputs / Fields

- **Style:** PrimeVue `outlined` input style (set globally in `app.ts`). Surface-card background, 1px border, control radius.
- **Search field:** `IconField` + `InputIcon` (Font Awesome `fa-search`) + `InputText`. The icon leads, the field fills. This is the canonical list-page search pattern.
- **Focus:** border shifts to Operational Navy, focus ring. No glow.
- **Error:** PrimeVue `p-invalid` class — red border + red helper text. Pair with a toast on submit; never rely on color alone.
- **Disabled:** PrimeVue disabled treatment. Never use a custom muted style.

### Cards / Containers

- **Corner Style:** Aura card radius (`8px`).
- **Background:** surface-card token (white in light, `surface-800` in dark).
- **Shadow Strategy:** none at rest (see Elevation). 1px border only.
- **Internal Padding:** `16px` (`p-4`). DataTable pages wrap the table in a `Card` with `#content`.
- **No nested cards.** A card inside a card is always wrong on this surface.

### Navigation (Sidebar)

- **Shell:** fixed left sidebar, collapsible (icon-only mode via `toggleSidebar`), overlay on mobile. `PanelMenu` with custom `pt` overrides removing borders and backgrounds — the menu reads as a list of controls, not a styled accordion.
- **Item:** transparent background, ink text, `10px 14px` padding, control radius. Icon (`menu-icon`) + label (`menu-label`).
- **Active route:** Instrument 50 background, Trust Blue 700 text. The only place a primary tint is used as a fill.
- **User section:** avatar initial in a circle, name, popup `Menu` for profile / dark-mode toggle / logout.
- **Mobile:** slide-in overlay with mask; outside-click closes. Toggle button is `mobile-menu-toggle`.

### Signature: The Two Layout Shells

- **AppLayout** (`resources/js/Layouts/admin.vue` → `Components/AppLayout.vue`): admin density. Sidebar + main + footer. Tables, filters, forms. Dense by default; breathing room comes from the card padding, not from oversized controls.
- **PosLayout** (`resources/js/Layouts/Components/PosLayout.vue`): POS speed. Fixed `PosShiftBar` at top (`pt-14` main), full-height scroll, no sidebar. Hard 768px viewport floor — below it, an unsupported-viewport message renders instead of the POS UI. Includes a keyboard skip-link (`#pos-main`) and `sr-only` utilities. Toasts are offset to `top: 64px` to clear the shift bar. Hit targets are larger; motion budget is tighter (instant or crossfade only).

### Chips / Tags

- **Tag** (PrimeVue): `rounded` + `severity="secondary"` for counts (e.g. products-per-brand). Small, tonal, never decorative.
- **Badge** (PrimeVue): severity `primary` for active filter counts on list pages.

### Toast / Confirm

- **Toast:** `life: 3000` default. Severity `success` / `error` / `warn` / `info`. POS surface uses a dedicated `group="pos"` with the offset hack above.
- **ConfirmDialog:** standard. `fa-exclamation-triangle` icon, reject `secondary`, accept inherits severity from action (delete = danger, restore = success).

## 6. Do's and Don'ts

Concrete guardrails. Every anti-reference in PRODUCT.md is enforced here by name.

### Do:

- **Do** use Operational Navy (`#00539b`) only for primary actions, active state, and key status — and keep it under ~10% of any screen (The One Instrument Rule).
- **Do** darken on hover/active (`#003d74` → `#002d57`). Primary controls depress; they never glow or scale.
- **Do** pair every status color with a label or icon. Color confirms; it never carries state alone (The Status-Pairs Rule).
- **Do** separate surfaces by surface token first, border second, shadow last (The Tonal-Layering Rule). Resting cards are flat with a 1px border.
- **Do** keep hit targets large on the POS surface and respect the 768px floor — the POS unsupported-viewport message is a feature, not a fallback.
- **Do** respect `prefers-reduced-motion` on every transition; POS-critical paths default to instant or crossfade regardless of the user setting.
- **Do** use Lato in weight and size for hierarchy. One family, multiple weights (The One-Family Rule).
- **Do** use sentence-case 14px/500 for labels. Labels read like labels, not kickers (The No-Kicker Rule).
- **Do** keep body text at ≥7:1 (Ink) and muted text at ≥4.5:1 (Muted) against surface-card in both themes. The tablet runs under bright retail light.
- **Do** import PrimeVue components directly from `primevue` and use Ziggy's `route()` for URLs — never hardcode.

### Don't:

- **Don't** use Operational Navy as a full-bleed background, gradient, or decorative tint (The No-Wash Rule). The only tinted background is Instrument 50 on the active sidebar route.
- **Don't** ship a shadow on a resting card (The Flat-By-Default Rule). Shadows are for overlays and dialogs only.
- **Don't** nest cards. A card inside a card is always wrong on this surface.
- **Don't** add a side-stripe border (`border-left` / `border-right` > 1px) as a colored accent on cards, list items, or alerts. Use a full border, a background tint, or nothing.
- **Don't** use gradient text (`background-clip: text` + gradient). Emphasis is weight or size, in Operational Navy.
- **Don't** add a tiny uppercase tracked eyebrow above every section. The all-caps kicker is the AI scaffold this system rejects (The No-Kicker Rule).
- **Don't** build the "hero-metric template" (big number, small label, supporting stats, gradient accent) on the dashboard. That is the generic-SaaS anti-reference from PRODUCT.md.
- **Don't** ship the cluttered legacy POS look: tiny fonts, dense unstyled tables, chrome competing with content. The POS is a modern instrument, not a cash-register emulator.
- **Don't** ship over-designed consumer-app flourish: playful animations, large decorative illustrations, gradient hero blobs. This is a tool, not a vibe.
- **Don't** introduce a second typeface for emphasis (The One-Family Rule) or a serif/display sans on the app surface.
- **Don't** mix Noir (login) tokens into the app surface, or Operational Navy into the login surface. The two themes are a deliberate tonal contrast.
- **Don't** use color as the sole carrier of state. A red border without a label fails the audit trail this product exists to provide.
- **Don't** animate layout properties (`width`, `height`, `padding`) where a transform or opacity will do. Motion is a budget, not a feature.