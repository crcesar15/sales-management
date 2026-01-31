# Phase 5: Detailed Developer Tasks

## 1. Backend Implementation

### Task 1.1: Aggregation Queries
- **Description**: Efficient reporting queries.
- **Steps**:
    1.  Create `ReportService`.
    2.  Method `getDailySales($startDate, $endDate)`: Use `groupBy(DB::raw('Date(created_at)'))`.
    3.  Method `getTopProducts()`: Join `order_items`, sum quantity, order by desc.
- **Acceptance Criteria**: Queries return accurate arrays of data.

### Task 1.2: Export Functionality
- **Description**: Download data.
- **Steps**:
    1.  Install `maatwebsite/excel`.
    2.  Create Exports: `php artisan make:export SalesExport`.
    3.  Controller Endpoint: `return Excel::download(new SalesExport, 'sales.xlsx');`.
- **Acceptance Criteria**: Download valid .xlsx file.

## 2. Frontend Implementation

### Task 2.1: Dashboard Widgets
- **Description**: At-a-glance stats.
- **Steps**:
    1.  `Dashboard.vue`.
    2.  Fetch stats props from Inertia.
    3.  Cards for: Today's Sales, Total Orders, Stock Alerts (< min access).
- **Acceptance Criteria**: Dashboard shows real numbers.

### Task 2.2: Charts Integration
- **Description**: Visual trends.
- **Steps**:
    1.  Install `chart.js` and `vue-chartjs`.
    2.  Implement `BarChart` component.
    3.  Feed data from `ReportService` (via Controller) into Chart dataset.
- **Acceptance Criteria**: Chart renders sales over last 7 days.

### Task 2.3: Detailed Reports Pages
- **Description**: Tabular data with filters.
- **Steps**:
    1.  `Reports/Sales.vue`.
    2.  Date Range Picker (PrimeVue Calendar).
    3.  "Filter" button triggers refresh of table data.
    4.  "Export" button hits the backend export endpoint.
- **Acceptance Criteria**: Filtering by date updates the displayed rows correctly.
