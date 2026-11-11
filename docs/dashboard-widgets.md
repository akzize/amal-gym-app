# Dashboard Widgets Report

## Overview

The dashboard ([app/Filament/Pages/Dashboard.php](../app/Filament/Pages/Dashboard.php)) has a **date filter** (`from` / `to`) at the top. All figures are computed in [app/Support/DashboardMetrics.php](../app/Support/DashboardMetrics.php), so cards, charts and tables agree with each other.

**Filter behavior**
- Default range: last 6 months, current month included (first day of 5 months ago -> end of this month).
- If `from` > `to`, the two dates are swapped automatically.
- The filter is kept in the URL and session.
- Widgets react to the filter only if they use `InteractsWithPageFilters`.

**Two accounting bases used**
- **Collected** = cash basis: payment installments counted by the date they were **paid** (`paid_at`).
- **Outstanding** = counted by the period a payment is **for**: `applies_to_date`, else `payment_date`, else `created_at`. Payments with status "free" are excluded.

**Layout:** 2-column grid. Order (`$sort`): StatsOverview (1), CollectedVsOutstandingChart (2), PaymentsByTypeChart (3), CollectedPaymentsTable (4), MonthlyTrainerPayments (5).

---

## Summary

| # | Widget | Type | Uses date filter? |
|---|--------|------|-------------------|
| 1 | StatsOverview | 7 KPI cards | Partly (4 of 7 cards) |
| 2 | CollectedVsOutstandingChart | Bar chart | Yes |
| 3 | PaymentsByTypeChart | Stacked bar chart | Yes |
| 4 | CollectedPaymentsTable | Table | Yes |
| 5 | MonthlyTrainerPayments | Table with actions | No (always current month) |

---

## 1. StatsOverview
File: [app/Filament/Widgets/StatsOverview.php](../app/Filament/Widgets/StatsOverview.php)

The subheading shows the active period (`from -> to`). Cards in two groups:

### Money cards (all filtered)

| Card | What it shows | How it is computed | Filter |
|------|---------------|--------------------|--------|
| **Collected** | Total cash received in the range, with a % change vs the previous period of the same length, and a monthly sparkline | Sum of `payment_installments.amount_paid` where `paid_at` is in range | Yes |
| **Outstanding** | Money still owed for payments belonging to the range, with monthly sparkline | Sum of `max(0, amount_due - amount_paid)` for non-free payments whose period is in range | Yes |
| **Collection rate** | % of **monthly fees** due that were paid. Green >= 90%, yellow >= 70%, red below. Shows "—" when nothing was due | `sum(amount_paid) / sum(amount_due)` for payments of type Monthly only | Yes |
| **Net** | Collected minus trainer payouts paid in the range | `collected - trainerPayoutsPaid` (from `TrainerPayoutInstallment.paid_at`). Red if negative | Yes |

### Member cards

| Card | What it shows | How it is computed | Filter |
|------|---------------|--------------------|--------|
| **Active trainees** | Number of currently active trainees, plus "new trainees" in the range as description | Active count = all `is_active` trainees (no date). New = `created_at` in range | Count: **No**. "New" description: **Yes** |
| **Unpaid this month** | Active trainees whose monthly fee for the **current month** is not settled. Clicking opens the trainees list with the same filter | `Trainee::unpaidForMonth(now)` | **No** (always current month) |
| **Expiring subscriptions** | Active subscriptions/insurances ending in the next 30 days | `status = active` and `end_date` between today and today + 30 | **No** (always from today) |

---

## 2. CollectedVsOutstandingChart
File: [app/Filament/Widgets/CollectedVsOutstandingChart.php](../app/Filament/Widgets/CollectedVsOutstandingChart.php)

- **Shows:** grouped bar chart, one pair of bars per month in the range: Collected (green) and Outstanding (amber).
- **Purpose:** compare what came in versus what is still owed, month by month.
- **Filter:** Yes. Months are all months the range touches.
- **Note:** collected is grouped by payment date, outstanding by the month the payment is for, so a month's bars measure different things (see recommendations).

## 3. PaymentsByTypeChart
File: [app/Filament/Widgets/PaymentsByTypeChart.php](../app/Filament/Widgets/PaymentsByTypeChart.php)

- **Shows:** stacked bar chart per month; each segment is a payment type (Monthly, Registration, Insurance, etc., Arabic name preferred), with a fixed 8-color palette.
- **Purpose:** see which revenue source makes up each month's collected cash.
- **Filter:** Yes. Same rows as the "Collected" card (cash basis).
- Types without a name appear as "—". More than 8 types reuse colors.

## 4. CollectedPaymentsTable
File: [app/Filament/Widgets/CollectedPaymentsTable.php](../app/Filament/Widgets/CollectedPaymentsTable.php)

- **Shows:** one row per paid installment in the range: paid date, trainee (Arabic name, fallback to name), group, payment type badge, amount paid. Footer sum of the amounts.
- **Purpose:** the detailed list behind the Collected card and charts; the table total equals the Collected card.
- **Actions:** clicking a row opens that payment's edit page. Default sort: newest first. Columns `paid_at` and `amount_paid` sortable.
- **Filter:** Yes.
- Footer total sums the current page/query per Filament's summarizer behavior; verify against the card when paginating.
- No search, no filters of its own.

## 5. MonthlyTrainerPayments
File: [app/Filament/Widgets/MonthlyTrainerPayments.php](../app/Filament/Widgets/MonthlyTrainerPayments.php)

- **Shows:** one row per trainer: name, salary type, expected payout, paid so far, remaining (red if > 0), status badge (paid / partial / unpaid). Hidden-by-default toggles: trainees count, groups count, created/updated.
- **Purpose:** manage trainer salary for the **current month**.
- **Row actions:**
  - Record payment (modal: choose month, shows due / paid / remaining, enter amount; creates a payout installment).
  - Print latest receipt.
  - View all receipts.
- **Filter:** **No.** Does not use page filters; always the current month (`now()`). Payment modal lets you pick another month, but the table does not.
- Expected amount is the live calculation until the first installment creates a payout record, then the stored `expected_amount`.
- Payouts recorded here feed the **Net** card (by payout `paid_at`, which does follow the filter).

---

## Notes and Recommendations

**Issues found**
1. **MonthlyTrainerPayments ignores the filter.** Users may expect it to follow the date range. Either add a month selector for this widget, or label it clearly "Current month".
2. **Duplicate heading and description** in this widget: `getDescription()` returns the same key as `getHeading()`; the table also sets `->heading()` with a different key. Pick one and give the description different text.
3. **Mixed bases in the "Collected vs Outstanding" chart:** Collected uses the paid date, Outstanding uses the period date. A payment made in March for February shows as collected in March and outstanding (if unpaid) in February. Add a tooltip or note in the chart description.
4. **Collection rate counts monthly fees only** while Outstanding includes all types; the labels should state this to avoid confusion.
5. **"Active trainees", "Unpaid this month", "Expiring subscriptions" ignore the filter** and sit among filtered cards. Add a visual note such as "now" or "today" in their description.
6. **Performance:** `collectedByMonth`, `collectedByTypeAndMonth` and `previous()->collected()` load rows into PHP and loop. Use SQL `GROUP BY` month (and type) for large data. Each widget also rebuilds metrics and re-runs queries independently.
7. **Empty `filters`, `headerActions` and `toolbarActions` placeholders** in MonthlyTrainerPayments can be removed.

**Suggested additions**
- Quick presets for the filter: this month, last month, last 3 months, this year, this season.
- Additional filters: by group, sport, payment type, trainer.
- Revenue by group/sport (pie or bar), to see which activities earn most.
- Top overdue trainees (list with amount and months overdue), linked to the trainee page.
- Trainer payouts chart (paid vs expected per month), and a cost/revenue trend line including Net per month.
- Attendance / new vs left trainees trend (new trainees per month chart; churn).
- Expiring subscriptions list (who and when), not just the count; link the card to the filtered subscriptions list as done for unpaid.
- Group capacity / occupancy widget.
- Export (CSV/PDF) for the collected payments table, and a search plus filter by type/group on it.
- Previous-period comparison on Outstanding and Net, not only Collected.
- Cache dashboard metrics for a short time (e.g. 1-5 minutes) and clear on payment save.
