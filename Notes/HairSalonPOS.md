# Hair Salon POS foundation

The salon-specific work is isolated on `feature/hair-salon-pos`. The standard POS and the general touchscreen POS are not changed by this foundation.

## Database setup

Run `SQL/20261107_hair_salon_pos_foundation.sql` once against the POS company database. The script uses `CREATE TABLE IF NOT EXISTS` and does not alter or repurpose the existing production employee, sales, inventory, or accounting tables.

## Foundation tables

- `salon_hairdressers`: salon staff roster and active/inactive status.
- `salon_services`: service catalogue, category, standard duration, and standard price.
- `salon_hairdresser_services`: which services each hairdresser is qualified/assigned to perform.
- `salon_commission_rules`: percentage or fixed commission rules. A rule may be global, service-specific, hairdresser-specific, or specific to one hairdresser/service combination.
- `salon_service_commissions`: transaction-level commission snapshot and payout/reversal status.

## Commission controls

1. Commission is intended to be earned only when the service line is completed and its sale is paid.
2. The eligible service amount is the service amount after line discounts; product sales are not included in the service commission base.
3. The checkout integration must resolve the most specific active rule for the hairdresser/service and snapshot the rule type, value, eligible amount, and calculated commission.
4. The unique source-document/line/hairdresser key prevents duplicate commission creation when a checkout request is retried.
5. A commission is not recalculated from current rules after posting. Payouts and reversals update the ledger status and retain the original calculation for audit.

## Implementation sequence

1. Hairdresser, service, and commission-rule maintenance screens.
2. Appointment and walk-in service workflow.
3. Touch POS service checkout with one or more assigned hairdressers per service line.
4. Paid/completed commission posting, payout controls, and commission reports.

The migration is the schema foundation only. It does not yet change the existing POS checkout or automatically post commissions.
