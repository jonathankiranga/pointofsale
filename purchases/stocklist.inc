<?php
$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
    
echo '<table class="table table-bordered"><thead><tr>'
        . '<th>Stock Code</th>'
        . '<th>Stock Description</th>'
        . '<th>Unit Of Measure (UOM)</th>'
        . '<th>Part per Unit(@ X 1)</th>'
        . '<th>Ave Cost per part</th>'
        . '<th class="number">Quantity to Order</th>'
        . '<th class="number">Price for the selected UOM</th>'
        . '<th class="number">Net Amount</th>'
        . '<th class="number">VAT Amount</th>'
        . '<th class="number">Gross Amount</th></tr></thead>';

$itemcode = $_POST['stockitemcode'];
$POSclass->Getitems($_POST['stockitemcode'],$_POST['quantity'][$itemcode],$_POST['salesprice'][$itemcode]);

echo '</table>';
echo '<div class="rightside">
	<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
        <input type="submit" name="submit" value="' . _('Add New Line') . '" />
	<input type="submit" name="submit" value="' . _('Enter Delivery Details and Confirm Order') . '"  />
</div>';

?>