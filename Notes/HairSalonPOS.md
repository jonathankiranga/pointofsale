# Hair Salon POS foundation

The salon-specific work is isolated on `feature/hair-salon-pos`. The standard POS and the general touchscreen POS are not changed by this foundation.

## Database setup

Run `SQL/20261107_hair_salon_pos_foundation.sql` once against the POS company database. Then apply the item-mapping and master-alignment migrations. The alignment migration backfills existing mapped salon services from ERP stockmaster data.

## Foundation tables

- `salon_hairdressers`: salon staff roster and active/inactive status.
- `salon_services`: salon-specific duration, salon selling price, description/notes, ERP item mapping and generated internal service reference. The service name is read from `stockmaster.descrip`; its category is read from `stockcategory.categorydescription`.
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

Create the service as an ERP stock item first. The ERP stock item is the POS transaction identity: its item code is written to the POS sales line and its posting group/VAT configuration drives accounting. The stock item description supplies the salon service name, and its `stockmaster.category` is resolved through `stockcategory` to supply the category description.

In Salon Management, select the ERP item code or barcode; the paired selector and read-only description/category fields are populated from the same stock item. Do not manually enter a separate service name or category. The salon service code is generated from the new `service_id` when the service is saved. Duration, salon selling price, salon notes, staff assignments and commission rules remain salon-specific.

Apply `SQL/20261107_hair_salon_pos_master_alignment.sql` once after `SQL/20261107_hair_salon_pos_item_mapping.sql` to synchronise existing mapped service names and categories from the ERP master.

Appointment customer names are recorded on the POS sales header while the existing configured POS debtor/customer account remains the accounting customer. If individual debtor-account posting is required per salon customer, the appointment booking form must later be connected to the ERP debtor master rather than using free-text customer names.
