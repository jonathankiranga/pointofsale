-- Add the user responsible for recording each commission payout.
ALTER TABLE salon_service_commissions
    ADD COLUMN payout_by VARCHAR(20) DEFAULT NULL AFTER payout_reference;
