# Hair Salon POS

The salon terminal uses the existing ERP POS receipt, payment, VAT and accounting workflow. ERP `stockmaster` is the single item master for both retail products and salon services.

## Data ownership

- `stockmaster` owns the item code, description, category, barcode, selling price, units, VAT and accounting posting configuration.
- `salon_service_settings` stores only salon-specific operational settings, keyed directly by `stockmaster.pkey`: service duration, active/inactive status and optional salon notes.
- `salon_hairdressers` stores the salon staff roster.
- `salon_hairdresser_services` assigns ERP stock items to hairdressers.
- `salon_commission_rules` stores percentage/fixed commission rules. A rule may be global, stock-item-specific, hairdresser-specific, or specific to a hairdresser and stock item.
- `salon_service_commissions` stores immutable transaction-level commission snapshots and payout/reversal status.
- `salon_appointment_lines` stores the ERP stock item selected for the booking, together with the agreed price and duration snapshot.

There is no second salon service catalogue. Salon item identity and commercial values are always read from ERP stockmaster. The salon settings table is not an item master.

## Commission controls

1. Commission is earned only when the service line is completed and its sale is paid.
2. The eligible service amount is the service amount after line discounts and excluding VAT; retail product sales are not included.
3. Checkout resolves the most specific active rule for the hairdresser and `stockmaster.pkey`, then snapshots the rule type, value, eligible amount and calculated commission.
4. A unique source-document/line/hairdresser key prevents duplicate commission creation when checkout is retried.
5. Historic commission entries retain their original calculation. Later changes to item prices or commission rules do not recalculate posted entries.

## Required migration order

Back up the company database before applying schema changes. Apply the migrations once, in this order:

1. `SQL/20261107_hair_salon_pos_foundation.sql`
2. `SQL/20261107_hair_salon_pos_operations.sql`
3. `SQL/20261107_hair_salon_pos_item_mapping.sql`
4. `SQL/20261107_hair_salon_pos_master_alignment.sql`
5. `SQL/20261107_hair_salon_stockmaster_direct.sql`
6. `SQL/20261107_hair_salon_pos_payout_audit.sql`
7. `SQL/20261107_hair_salon_appointment_pos_integration.sql`

The stockmaster-direct migration converts staff assignments, commission rules, commission history and appointment lines from the old salon service IDs to direct `stockmaster.pkey` references. It then removes the duplicate `salon_services` catalogue. Its diagnostic query must report zero unmapped references before continuing. It requires the unique `stockmaster.pkey` key created by the preceding master-alignment migration.

## Configure a salon service

1. Create the item once in the ERP Stocks catalogue. Use an item description/category suitable for a salon service and configure its selling price, units, VAT and sales posting group correctly.
2. In Salon Management → Services, select that existing ERP stock item and configure only its salon duration, active status and optional notes.
3. Assign eligible hairdressers under Staff skills.
4. Configure an active commission rule with an effective date. Checkout is rejected if no applicable rule exists for the selected hairdresser and stock item.

Do not create a second service code, service name, category or price in Salon Management. Changes to the ERP item description, category or selling price are reflected automatically by salon operations.

## Appointment booking and checkout

1. Book the customer, date/time, ERP stock item and assigned hairdresser in Salon Management → Appointments.
2. The booking snapshots the ERP selling price and configured salon duration. Later price or duration changes do not silently rewrite an existing booking.
3. Mark the appointment Arrived, then select Start Service.
4. Salon POS loads the booked item, agreed price and hairdresser. The booked line cannot be removed or have its quantity edited.
5. Add extra salon services and retail products to the same transaction. Each additional salon service requires an assigned hairdresser.
6. Complete payment using the normal POS cash or manually verified M-Pesa workflow.
7. The POS revalidates the stock item, staff assignment and commission rule. On successful commit, it links the receipt, completes the appointment and records eligible commissions in the same transaction.

If checkout fails, the sale, accounting, commission, appointment-link and completion changes roll back. The appointment remains In Service and can be resumed.

## Commission payout tracking

The most specific active rule is selected: hairdresser plus stock item, then a single-scope rule, then a global rule. Percentage commission applies to the eligible amount excluding VAT; fixed commission applies per service quantity. The payout screen records the payment/voucher reference against pending commission records. It does not itself create a cashbook payment, bank transaction or general-ledger voucher.

## Operational cautions

- Do not enable a physical inventory item as a salon service if selling it should decrement inventory as an ordinary retail product.
- A salon item must have a valid ERP selling price, units, VAT category and sales/VAT accounts before checkout.
- Deactivating a salon setting removes the item from the salon service picker but does not deactivate the underlying ERP item in retail POS.
- Appointment customer names remain free-text on the appointment. The configured POS debtor remains the accounting customer unless the booking workflow is separately connected to the ERP debtor master.
