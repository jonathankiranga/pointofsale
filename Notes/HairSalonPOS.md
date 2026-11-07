# Hair Salon POS foundation

The salon-specific work is isolated on `feature/hair-salon-pos`. The standard POS and the general touchscreen POS are not changed by this foundation.

## Database setup

Run `SQL/20261107_hair_salon_pos_foundation.sql` once against the POS company database. Then apply the item-mapping and master-alignment migrations. The alignment migration adds a database foreign key from each salon service to its ERP stockmaster row; it does not overwrite the manually maintained service code, name or category.

## Foundation tables

- `salon_hairdressers`: salon staff roster and active/inactive status.
- `salon_services`: manually maintained service code, service name and category, salon-specific duration and selling price, description/notes, plus a foreign-key reference to the selected ERP stockmaster row.
- `salon_hairdresser_services`: which services each hairdresser is qualified/assigned to perform.
- `salon_commission_rules`: percentage or fixed commission rules. A rule may be global, service-specific, hairdresser-specific, or specific to one hairdresser/service combination.
- `salon_service_commissions`: transaction-level commission snapshot and payout/reversal status.

## Commission controls

1. Commission is intended to be earned only when the service line is completed and its sale is paid.
2. The eligible service amount is the service amount after line discounts; product sales are not included in the service commission base.
3. The checkout integration must resolve the most specific active rule for the hairdresser/service and snapshot the rule type, value, eligible amount, and calculated commission.
4. The unique source-document/line/hairdresser key prevents duplicate commission creation when a checkout request is retried.
5. A commission is not recalculated from current rules after posting. Payouts and reversals update the ledger status and retain the original calculation for audit.

## Required migration order

1. `SQL/20261107_hair_salon_pos_foundation.sql`
2. `SQL/20261107_hair_salon_pos_operations.sql`
3. `SQL/20261107_hair_salon_pos_item_mapping.sql`
4. `SQL/20261107_hair_salon_pos_master_alignment.sql`
5. `SQL/20261107_hair_salon_pos_payout_audit.sql`
6. `SQL/20261107_hair_salon_appointment_pos_integration.sql`

## Appointment-to-POS workflow

Apply `SQL/20261107_hair_salon_pos_operations.sql` and then `SQL/20261107_hair_salon_appointment_pos_integration.sql` after the foundation, item-mapping and ERP master-alignment migrations.

1. Book the customer, date/time, service and an assigned hairdresser in Salon Management.
2. Mark the appointment Arrived when the customer checks in.
3. Select **Start Service**. The appointment loader locks the booking, validates its service mapping and hairdresser assignment, and changes the appointment and line to `in_service`.
4. Salon POS preloads the customer, appointment reference, booked service, agreed booking price and assigned hairdresser. The booked service cannot be removed from the basket. Additional services and retail products may be added.
5. Complete payment through the existing POS cash or M-Pesa workflow.
6. The receipt transaction creates the normal ERP sale and accounting postings, snapshots eligible hairdresser commissions, inserts the unique appointment-to-receipt link, and changes the appointment and service line to `completed` in the same database transaction.
7. If checkout fails, all sale, accounting, commission, appointment-link and completion changes roll back. The appointment remains `in_service` and can be resumed from the schedule.

The unique appointment key in `salon_appointment_transactions` prevents a second receipt from being linked to the same booking. A repeated request using the same receipt number remains subject to the existing POS idempotency checks.

Cancelled and no-show appointments cannot be started. Appointments that have started cannot be cancelled or marked no-show. No commission is created for cancelled, no-show or unfinished appointments because commission rows are written only inside a successfully committed paid POS sale.

## ERP item master and salon service setup

The salon service form keeps its service code, service name and category editable. These are salon-facing business fields and may differ from the ERP stock item description or stock category.

Select the ERP item code or barcode to link the salon service to an existing stockmaster record. The paired selector follows the selected stock item. The POS uses the ERP stock item code for the sales line and obtains its units, VAT and posting configuration from that stockmaster record. The salon service name is used as the service description on the POS sales line.

The database relationship is stored using `stockmaster.pkey`, not a copied item-code string. The master-alignment migration adds a unique key to `stockmaster.pkey`, adds `salon_services.stockmaster_pkey`, backfills existing mappings, removes the copied item-code/barcode columns and creates a foreign key with `ON DELETE RESTRICT` and `ON UPDATE RESTRICT`. A unique key on the salon service reference prevents one ERP stock item from being assigned to multiple salon services. This prevents deletion or identity-key changes to a stock item while a salon service references it.

Apply `SQL/20261107_hair_salon_pos_master_alignment.sql` once after `SQL/20261107_hair_salon_pos_item_mapping.sql`. The migration preserves service codes, names and categories. It requires existing `stockmaster.pkey` values to be unique; resolve any duplicate pkey values before running it. The migration removes the temporary `pos_itemcode` and `pos_barcode` columns after converting existing mappings to the foreign-key reference. Legacy mappings that match more than one stockmaster row are deliberately left unlinked and must be selected again in Salon Management.

Appointment customer names are recorded on the POS sales header while the existing configured POS debtor/customer account remains the accounting customer. If individual debtor-account posting is required per salon customer, the appointment booking form must later be connected to the ERP debtor master rather than using free-text customer names.
