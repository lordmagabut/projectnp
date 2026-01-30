# RAB Import Workflow — AHSP, Materials & Labor

## Overview

This document describes a recommended workflow to import RAB (budget/BOQ) data into the system, including integration and matching against AHSP (standard price list), materials, and labor entries. The steps prioritize data validation, deterministic matching, reconciliation, and safe rollback.

## Prerequisites

- Exported RAB file(s) in CSV or Excel format.
- AHSP master data available in a machine-readable format (CSV, Excel, or database).
- Material catalog and labor rate tables accessible.
- User with import privileges and access to staging environment.

## Key Concepts

- RAB: Detailed budget items (codes, descriptions, quantities, units, unit prices).
- AHSP: Standardized reference of item codes, descriptions, unit prices, and categories.
- Material: Inventory/catalog entries (SKU, unit, conversion factors, VAT/Tax rules).
- Labor (Upah): Rate templates per role, unit (hour/day), and applicable multipliers.

## Data Preparation

1. Normalize file encoding to UTF-8 and remove hidden characters.
2. Ensure consistent column headers. Recommended canonical columns:
   - `item_code`, `description`, `quantity`, `unit`, `unit_price`, `total_price`, `tax`, `notes`
3. Trim whitespace and standardize numeric formats (decimal separators).
4. If possible, split combined descriptions into structured fields: category, subcategory, material/labor flag.

## Mapping & Transformation Rules

- Field mapping must be configured before import (source column -> target field).
- Units: define unit conversion table (e.g., m -> meter, pcs -> piece).
- AHSP matching strategy (in order):
  1. Exact `item_code` match.
  2. Fuzzy match on normalized `description` + `unit` + `category`.
  3. Manual review list when confidence < threshold.
- Material matching: try SKU or normalized description, then fallback to manual.
- Labor mapping: match by role/description and unit (e.g., `man-day`).

## Import Workflow Steps

1. Ingest: upload RAB file to staging area.
2. Auto-parse: detect delimiter and parse rows into staging table.
3. Pre-validation checks:
   - Required columns present.
   - Quantity > 0 and numeric.
   - Units recognized or flagged.
   - Total_price = quantity * unit_price (within tolerance).
4. AHSP auto-match pass using configured matching strategy. Record match score.
5. Material and Labor auto-match pass.
6. Flag rows that need manual review (no match, low confidence, unit mismatch, price variance > X%).
7. Allow user to resolve flagged rows in a UI: choose AHSP item, select material SKU, correct unit or quantity.
8. Final validation: ensure no unresolved critical flags.
9. Import commit: create RAB records in production tables, link to matched AHSP/material/labor IDs, write audit log.
10. Post-import reconciliation: run summary checks (counts, totals, sample item checks).

## Validation Rules & Business Logic

- Price variance tolerance: configurable (e.g., 20%). If incoming unit_price deviates from AHSP by more than tolerance, flag as review.
- Tax handling: map VAT flags from AHSP or material master to RAB lines; compute net/gross consistently.
- Unit conversion: apply conversion factors before price comparison.
- Deduplicate: detect duplicate rows by `item_code`+`unit`+rounded `unit_price`.

## Error Handling & Rollback

- Import should run in a transaction-per-file or transaction-per-batch model.
- If import fails pre-commit, discard staging changes and present errors.
- If import partially commits (long-running), write compensation records and a clear rollback procedure:
  - Revert created RAB records using import batch ID.
  - Restore previous AHSP links if overwritten.

## Reconciliation & Reporting

- After import, generate an import report including:
  - Row counts: imported, skipped, flagged.
  - Total RAB value vs sum of matched AHSP values.
  - List of flagged items requiring action.
- Store import metadata: user, timestamp, file hash, settings used.

## Automation & Scheduling

- For recurring imports, configure scheduled jobs with notification on completion/errors.
- Maintain a retry policy for transient errors (e.g., DB timeouts).

## Testing & Acceptance

- Create representative test files that cover:
  - Exact AHSP matches
  - Fuzzy matches and ambiguous descriptions
  - Unit conversion cases
  - Tax scenarios
- Run imports in staging and verify reconciliation reports.

## Appendix — Suggested CSV Columns

- `row_no`
- `item_code`
- `description`
- `category`
- `quantity`
- `unit`
- `unit_price`
- `total_price`
- `tax_rate`
- `notes`

## Appendix — Sample Field Mapping Table

| Source Column | Target Field | Notes |
|---|---|---|
| `ItemCode` | `item_code` | Prefer exact AHSP code if present |
| `Desc` | `description` | Normalize whitespace, lowercase |
| `Qty` | `quantity` | Numeric only |
| `UOM` | `unit` | Map to canonical units |
| `Price` | `unit_price` | Currency normalized |

## Quick Checklist (Before Import)

- [ ] Source file validated (encoding, headers)
- [ ] AHSP/Material/Labor reference datasets up-to-date
- [ ] Mapping configuration reviewed
- [ ] Backup or staging enabled
- [ ] User assigned for manual review tasks

---

If you want, I can also:
- produce a sample import CSV template,
- generate the matching confidence algorithm pseudo-code,
- or add a UI mockup for manual resolution steps.
