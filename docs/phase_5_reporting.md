# Phase 5: Reporting & Advanced Features

**Goal**: Insights, polished dashboard, and data export.

## 1. Backend Implementation
### Reporting Queries
- **SalesReport**: Group by Date (Daily), by Month. Filter by Store/User.
- **ProductReport**: Top selling items. Low stock items.
- **ProfitLoss**: `(Sales - Cost of Goods Sold) - Expenses`.

### Export
- Implement `Maatwebsite\Excel` exports for tables.

## 2. Frontend Implementation
### Dashboard
- **Key Metrics Cards**: Total Sales Today, Total Orders, Low Stock Count.
- **Charts**:
    - Bar Chart: Sales last 7 days.
    - Pie Chart: Top Categories.
    - Use `chart.js` or `apexcharts`.

### Reports Pages
- Filterable tables (Date Range Picker is crucial).
- "Export to Excel" / "Print PDF" buttons on report views.

## 3. Advanced Features (Optional)
- **Stock Alerts**: Email notification when stock hits `min_stock_level`.
- **Returns**: Handling sales returns (Restock item, Refund money).

## 4. Deliverables
- [ ] Executive Dashboard.
- [ ] Sales Reports (Daily/Monthly).
- [ ] Inventory Reports.
- [ ] Export capabilities.
