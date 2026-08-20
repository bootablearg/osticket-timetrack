# Changelog

## 1.0.0 — 2026-08-20

First public release.

- Time logging on replies and internal notes, with time types and a billable
  flag, stored in the plugin's own `ost_timetrack` table.
- Free-form time input: `90`, `1:30`, `1h30`, `2h`, `1,5h`, `45m`.
- Optional rounding (5/15/30/60 minutes) and mandatory time.
- Time badge per thread entry and total on the ticket summary.
- Report page with filters (date range, organization, agent, type, billable)
  and per-organization / per-type breakdowns.
- CSV export with UTF-8 BOM and semicolon separators.
- REST endpoints for per-ticket and per-period totals, plus `tt_minutes` on
  ticket creation through osTicket's own API.
- Queue column annotation with the time logged per ticket.
- Ships `scp/apps.php`, which osTicket references but does not distribute.
- English and Spanish (Argentina); adding a locale is one PHP file.
- Optional 7-line core patch for the built-in queue CSV export.
