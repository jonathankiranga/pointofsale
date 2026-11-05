
<?php
// Assuming you have a mysqli connection in $mysqli

// Define prefix (can also come from DB, if multiple types of receipts exist)
$prefix = 'INV';

// Start a transaction to ensure atomicity (prevents race conditions)
$mysqli->begin_transaction();

try {
    // Step 1: Get the current suffix
    $stmt = $mysqli->prepare("SELECT id, current_suffix FROM receipt_master WHERE prefix = ? FOR UPDATE");
    $stmt->bind_param("s", $prefix);
    $stmt->execute();
    $stmt->bind_result($master_id, $current_suffix);
    $stmt->fetch();
    $stmt->close();

    // Generate the next suffix
    $new_suffix = $current_suffix + 1;
    
    // Generate the final receipt number
    $receipt_number = $prefix . str_pad($new_suffix, 5, "0", STR_PAD_LEFT);  // e.g., INV00001
    
    // Step 2: Update the `current_suffix` in `receipt_master`
    $stmt = $mysqli->prepare("UPDATE receipt_master SET current_suffix = ?, last_generated = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $new_suffix, $master_id);
    $stmt->execute();
    $stmt->close();

    // Step 3: Insert into `sales_receipts` with the generated receipt number
    $total = 200.00;
    $cash = 250.00;
    $change_amount = 50.00;
    $stmt = $mysqli->prepare("INSERT INTO sales_receipts (receipt_number, total, cash, change_amount) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sddd", $receipt_number, $total, $cash, $change_amount);
    $stmt->execute();
    $stmt->close();

    // Commit the transaction
    $mysqli->commit();
    
    echo "Receipt generated: " . $receipt_number;

} catch (Exception $e) {
    $mysqli->rollback();  // Rollback if there's an error
    echo "Failed to generate receipt: " . $e->getMessage();
}

$mysqli->close();
?>

<?php
// load_all_stock.php

// Mock database (replace with actual database query)
$products = [
    ['barcode' => '123456789012', 'description' => 'Apple', 'unit_price' => '3.00', 'vat' => '16'],
    ['barcode' => '987654321098', 'description' => 'Orange', 'unit_price' => '2.50', 'vat' => '16'],
    ['barcode' => '456123789012', 'description' => 'Banana', 'unit_price' => '1.50', 'vat' => '16'],
    ['barcode' => '321654987012', 'description' => 'Grapes', 'unit_price' => '4.00', 'vat' => '16'],
    ['barcode' => '123456789012', 'description' => 'Apple', 'unit_price' => '3.00', 'vat' => '16'],
    ['barcode' => '987654321098', 'description' => 'Orange', 'unit_price' => '2.50', 'vat' => '16'],
    ['barcode' => '456123789012', 'description' => 'Banana', 'unit_price' => '1.50', 'vat' => '16'],
    ['barcode' => '321654987012', 'description' => 'Grapes', 'unit_price' => '4.00', 'vat' => '16'],
    ['barcode' => '123456789012', 'description' => 'Apple', 'unit_price' => '3.00', 'vat' => '16'],
    ['barcode' => '987654321098', 'description' => 'Orange', 'unit_price' => '2.50', 'vat' => '16'],
    ['barcode' => '456123789012', 'description' => 'Banana', 'unit_price' => '1.50', 'vat' => '16'],
    ['barcode' => '321654987012', 'description' => 'Grapes', 'unit_price' => '4.00', 'vat' => '16'],
    ['barcode' => '123456789012', 'description' => 'Apple', 'unit_price' => '3.00', 'vat' => '16'],
    ['barcode' => '987654321098', 'description' => 'Orange', 'unit_price' => '2.50', 'vat' => '16'],
    ['barcode' => '456123789012', 'description' => 'Banana', 'unit_price' => '1.50', 'vat' => '16'],
    ['barcode' => '321654987012', 'description' => 'Grapes', 'unit_price' => '4.00', 'vat' => '16'],
  
    // Add more products here
];


header('Content-Type: application/json');

echo json_encode($products);
