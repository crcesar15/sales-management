# Replacement-cost pricing

## Cost definitions

- **Actual stock cost** is the quantity-weighted cost of remaining active, unexpired batches in the permitted stores. Missing costs remain unknown; the UI reports partial coverage.
- **Latest received cost** and received-cost history come from purchase receipts, including allocated purchase-order discounts. Historical batch costs are never rewritten by repricing.
- **Replacement cost** is a snapshot of the selected supplier's normal all-in price, divided by the purchasing-unit conversion factor. A separately entered PO discount does not reduce this reference.
- Existing `purchase_price` values are unverified legacy references, not historical batch costs.

Purchase prices already include purchasing costs. If a temporary discount is embedded in the entered unit price instead of recorded separately, the system cannot infer an undiscounted normal price.

## Pricing behavior

The inventory variant workspace has a dedicated **Pricing** tab for costs, suppliers, margins, suggestions, and cost history. **Overview** contains identification and inventory policy. Each tab saves only its own fields and retains unsaved edits when switching tabs or saving the other form. Price Review links open the Pricing tab directly.

`margin_type` and `margin_value` are stable targets. Current gross margin is derived independently. Editing a selling price never rewrites its target.

- Percentage target: `replacement cost / (1 - target / 100)`.
- Fixed target: `replacement cost + target amount per base unit`.
- Package costs and fixed targets scale by the package conversion factor.
- Suggestions round upward to cents, matching the stored selling-price precision.

Suggestions are applied explicitly and saved through the existing inventory forms. No supplier-cost operation changes selling prices. Transaction-level reference checks reject stale pricing saves.

## Supplier lifecycle

Creating the sole eligible catalog offer selects it automatically. Several offers, including different purchasing packages, require an explicit selection. Catalog changes to the selected offer update the reference; unrelated metadata edits do not.

A draft PO shows an impact preview. Approval selects its supplier and publishes a reference snapshot without the separate PO discount. When a variant appears on multiple lines with different normalized prices, select one line as the pricing reference before approval.

Cancellation invalidates the source PO's snapshots. If its snapshot remains current, the last valid reference is restored. Newer approvals or manual selections take precedence.

Freshness is manual. Mark the reference or catalog offer outdated when it needs verification. Conversion changes and unavailable offers also surface a freshness warning. Historical conversion factors remain snapshotted.

## Price Review alerts

Stock Alerts has a **Price Review** tab with reason, severity, and review-status filters. One row groups a variant's base-unit and package pricing issues.

- Below replacement cost and below target are persistent conditions.
- Cost increases and decreases are reviewable change events.
- Missing targets/references and outdated references require configuration or verification.
- Acknowledging changes never suppresses persistent conditions.
- Summary counts represent active reviews, even while browsing reviewed changes.

Prices are global to a variant. Store filters restrict visible variants, not their pricing. Administrators can review new products before their first receipt.

Inventory editing controls pricing mutations. Stock-alert access alone does not expose replacement-cost details. Actual batch-cost summaries additionally require the existing profitability permission and store scope.

## Fresh-database deployment

This feature is deployed to a new database. Run the application's migrations and seeders normally:

```bash
php artisan migrate --seed --no-interaction
```

No pricing initialization command or data backfill is required. Creating the sole eligible supplier offer establishes the initial reference automatically. Multiple offers can be selected manually, and purchase-order approval publishes subsequent supplier references.

Schema changes follow this repository's development convention: existing-table migrations are edited in place. Rebuilding that development schema requires an explicitly approved `migrate:fresh --seed`; a standard pending-migration run alone does not apply edits to previously run migrations.
