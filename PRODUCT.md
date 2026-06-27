# Product

## Register

product

## Users

Two primary personas, each on different surfaces of the same system:

- **Admin** (store owner / operations manager): works the admin dashboard across desktop, often during back-office hours. Primary jobs: maintain inventory accuracy, approve purchasing, configure stores and users, read reports. Context: multitasking, ambient office light, wants full visibility and an honest audit trail. Frustrated by stock discrepancies, manual reconciliation, and tools that hide "what actually happened."
- **Sales Rep**: works the POS surface, frequently on a tablet at the counter under bright retail lighting, often with a customer waiting. Primary job: complete a sale (search → cart → payment → receipt) in under 60 seconds. Frustrated by slow checkout, products they can't find, and any UI that demands precision they don't have time for.

Both users share one system; the register serves them differently on each surface. The admin surface rewards density and clarity; the POS surface rewards speed, large hit targets, and motion-reduced calm.

## Product Purpose

A web-based Sales Management System for retail stores (with future storehouse support) that unifies inventory tracking, point-of-sale operations, purchasing workflows, and reporting under one auditable platform. It exists because retail operators lose revenue to stock discrepancies, untracked sales, and manual reconciliation; success is stock discrepancy under 2%, a sale completed in under 60 seconds, a fully tracked purchase-to-reception cycle, and 100% of critical events recorded in the activity log. The product is a tool, not a marketing surface — design serves the operator getting the job done.

## Brand Personality

Fast, precise, clinical.

Voice: direct, no decoration. Tone: tool-native, not consumer-friendly. The interface speaks in numbers, status, and the next action — not in adjectives. Emotional goal: the operator should feel in control and un-rushed even when the queue is long; the admin should feel they are seeing the truth of the operation, not a sales pitch about it.

## Anti-references

- **Cluttered legacy POS terminals.** Cramped 90s-style interfaces with tiny fonts, dense unstyled tables, no breathing room, and chrome competing with content. The POS surface must feel like a modern instrument, not a cash register emulator.
- **Generic SaaS dashboard chrome.** Indigo gradients, hero-metric templates, stock illustrations, marketing-style hero sections inside the app. This is not a marketing surface.
- **Over-designed consumer-app flourish.** Playful animations, large decorative illustrations, gradient hero blobs, lifestyle-app styling. A tool, not a vibe.

## Design Principles

1. **Show the number, not the decoration.** Data and status carry the interface. Decoration earns its place only when it improves scan speed or clarifies state — never as atmosphere.
2. **Speed is a feature.** The POS sale-in-under-60-seconds criterion is a design constraint, not just a perf target. Fewer steps, large hit targets, keyboard-first flows, no motion that delays the next action.
3. **Calm under pressure.** Retail lighting and a waiting customer raise the cost of every visual decision. High-contrast, motion-reduced defaults, readable at arm's length on a tablet. The interface stays quiet when the operator is busy.
4. **Trust through an honest audit trail.** Every critical event is visible and traceable. Surfaces surface state changes plainly; nothing important hides behind a tooltip or a buried tab.
5. **One system, two registers.** Admin density and POS speed share a brand and tokens but diverge in layout, hit-target size, and motion budget. Refuse the reflex to make every screen look like the dashboard.

## Accessibility & Inclusion

Reduced-motion is a first-class default, not a fallback: the POS runs on tablets in bright retail lighting where animated transitions cost the operator time and attention. Priorities:

- `prefers-reduced-motion` respected on every transition; POS-critical flows default to instant or crossfade only.
- High-contrast defaults readable under bright ambient light; body text meets WCAG 2.1 AA (4.5:1) in both light and dark themes.
- Large hit targets on the POS surface (arm's-length tablet use); keyboard-navigable admin surfaces.
- Color is never the sole carrier of state — status pairs color with a label or icon.