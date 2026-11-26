<?php
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');

$Title = _('Selling Price Setup');
include('includes/header.inc');

function PricingEscape($value) {
    return str_replace("'", "''", trim((string)$value));
}

$mode = isset($_SESSION['POS_PricingMethod']) ? $_SESSION['POS_PricingMethod'] : 'markup';
$rate = isset($_SESSION['POS_PricingRate']) ? (float)$_SESSION['POS_PricingRate'] : 0;

if (isset($_POST['save_pricing'])) {
    $postedMode = isset($_POST['pricing_method']) ? $_POST['pricing_method'] : '';
    $postedRate = isset($_POST['pricing_rate']) ? (float)$_POST['pricing_rate'] : -1;
    if (!in_array($postedMode, array('markup', 'margin'), true) || $postedRate < 0 || $postedRate >= 100) {
        prnMsg(_('Choose a valid pricing method and enter a percentage from 0 up to (but not including) 100.'), 'error');
    } else {
        $safeMode = PricingEscape($postedMode);
        $safeRate = number_format($postedRate, 4, '.', '');
        DB_query("INSERT INTO config (confname, confvalue) VALUES ('POS_PricingMethod', '".$safeMode."') ON DUPLICATE KEY UPDATE confvalue=VALUES(confvalue)", $db);
        DB_query("INSERT INTO config (confname, confvalue) VALUES ('POS_PricingRate', '".$safeRate."') ON DUPLICATE KEY UPDATE confvalue=VALUES(confvalue)", $db);
        $_SESSION['POS_PricingMethod'] = $postedMode;
        $_SESSION['POS_PricingRate'] = $postedRate;
        $mode = $postedMode;
        $rate = $postedRate;
        prnMsg(_('Pricing formula saved.'), 'success');
    }
}

if (isset($_POST['apply_prices']) && isset($_POST['itemcode']) && is_array($_POST['itemcode'])) {
    $updated = 0;
    foreach ($_POST['itemcode'] as $postedCode) {
        $code = PricingEscape($postedCode);
        $itemResult = DB_query("SELECT itemcode, averagestock, partperunit FROM stockmaster WHERE itemcode='".$code."' AND COALESCE(inactive,0)=0", $db);
        if ($item = DB_fetch_array($itemResult)) {
            $parts = (float)$item['partperunit'];
            if ($parts <= 0) {
                $parts = 1;
            }
            $averageCost = (float)$item['averagestock'];
            $baseCostPerLoose = $averageCost / $parts;

            // Allocate each GRN's freight to this item by its share of that delivery's stock value.
            $freightSql = "SELECT
                COALESCE(SUM(
                    CASE WHEN delivery_totals.total_value > 0
                    THEN COALESCE(ph.freight,0) * line_values.line_value / delivery_totals.total_value
                    ELSE 0 END
                ),0) AS allocated_freight,
                COALESCE(SUM(line_values.loose_quantity),0) AS received_loose
                FROM (
                    SELECT stockledger.invref, stockledger.itemcode,
                           SUM(COALESCE(stockledger.price,0) * (COALESCE(stockledger.fulqty,0) * COALESCE(stockmaster.partperunit,1) + COALESCE(stockledger.loosqty,0))) AS line_value,
                           SUM(COALESCE(stockledger.fulqty,0) * COALESCE(stockmaster.partperunit,1) + COALESCE(stockledger.loosqty,0)) AS loose_quantity
                    FROM stockledger
                    INNER JOIN stockmaster ON stockmaster.itemcode=stockledger.itemcode
                    WHERE stockledger.doctyp='30'
                    GROUP BY stockledger.invref, stockledger.itemcode
                ) line_values
                INNER JOIN purchaseheader ph ON ph.documentno=line_values.invref AND ph.documenttype='30'
                INNER JOIN (
                    SELECT invref,
                           SUM(COALESCE(price,0) * (COALESCE(fulqty,0) * COALESCE(partperunit,1) + COALESCE(loosqty,0))) AS total_value
                    FROM stockledger
                    INNER JOIN stockmaster ON stockmaster.itemcode=stockledger.itemcode
                    WHERE doctyp='30'
                    GROUP BY invref
                ) delivery_totals ON delivery_totals.invref=line_values.invref
                WHERE line_values.itemcode='".$code."'";
            $freightResult = DB_query($freightSql, $db);
            $freightRow = DB_fetch_array($freightResult);
            $freightPerLoose = 0;
            if ($freightRow && (float)$freightRow['received_loose'] > 0) {
                $freightPerLoose = (float)$freightRow['allocated_freight'] / (float)$freightRow['received_loose'];
            }

            $landedCost = $baseCostPerLoose + $freightPerLoose;
            if ($landedCost <= 0) {
                continue;
            }
            if ($mode === 'margin') {
                $sellingPrice = $landedCost / (1 - ($rate / 100));
            } else {
                $sellingPrice = $landedCost * (1 + ($rate / 100));
            }
            $sellingPrice = round($sellingPrice, 2);
            DB_query("UPDATE stockmaster SET sellingprice=".number_format($sellingPrice, 2, '.', '')." WHERE itemcode='".$code."'", $db);
            $updated++;
        }
    }
    prnMsg(sprintf(_('Selling prices updated for %s stock item(s).'), $updated), 'success');
}

if (!isset($_POST['pricing_method'])) {
    $mode = isset($_SESSION['POS_PricingMethod']) ? $_SESSION['POS_PricingMethod'] : 'markup';
} else {
    $mode = $_POST['pricing_method'];
}
if (!isset($_POST['pricing_rate'])) {
    $rate = isset($_SESSION['POS_PricingRate']) ? (float)$_SESSION['POS_PricingRate'] : 0;
} else {
    $rate = (float)$_POST['pricing_rate'];
}

echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/inventory.png" alt="" /> '._('Selling Price Setup').'</p>';
echo '<div class="card"><div class="card-body">';
echo '<h4>'._('Pricing Formula').'</h4>';
echo '<form method="post" action="'.htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8').'">';
echo '<input type="hidden" name="FormID" value="'.htmlspecialchars($_SESSION['FormID'], ENT_QUOTES, 'UTF-8').'" />';
echo '<div class="form-group"><label>'._('Calculation method').'</label> ';
echo '<select class="form-control" name="pricing_method">';
echo '<option value="markup"'.($mode==='markup'?' selected="selected"':'').'>'._('Markup on cost').'</option>';
echo '<option value="margin"'.($mode==='margin'?' selected="selected"':'').'>'._('Gross margin').'</option>';
echo '</select></div>';
echo '<div class="form-group"><label>'._('Percentage (%)').'</label> <input class="form-control" type="number" name="pricing_rate" min="0" max="99.99" step="0.01" required="required" value="'.htmlspecialchars((string)$rate, ENT_QUOTES, 'UTF-8').'" /></div>';
echo '<button class="btn btn-primary" type="submit" name="save_pricing" value="1">'._('Save Formula').'</button>';
echo '</form>';
echo '<p class="help-block">'._('Landed loose-unit cost = average purchase cost per loose unit + allocated delivery freight per loose unit. Freight is allocated to products by their value within each GRN.').'</p>';
echo '<p class="help-block">'.($mode==='margin' ? _('Selling price = landed cost / (1 - gross margin percentage).') : _('Selling price = landed cost x (1 + markup percentage).')).'</p>';
echo '</div></div>';

echo '<form method="post" action="'.htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8').'">';
echo '<input type="hidden" name="FormID" value="'.htmlspecialchars($_SESSION['FormID'], ENT_QUOTES, 'UTF-8').'" />';
echo '<div class="card"><div class="card-body"><h4>'._('Review and Apply Selling Prices').'</h4>';
echo '<p>'._('Select the stock items to update. The current retail price is shown alongside the calculated price.').'</p>';
echo '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr>';
echo '<th>'._('Apply').'</th><th>'._('Stock Code').'</th><th>'._('Description').'</th><th class="number">'._('Average Cost / Full Unit').'</th><th class="number">'._('Parts per Unit').'</th><th class="number">'._('Allocated Freight / Loose Unit').'</th><th class="number">'._('Landed Cost / Loose Unit').'</th><th class="number">'._('Current Retail Price').'</th><th class="number">'._('Calculated Retail Price').'</th>';
echo '</tr></thead><tbody>';

$stockResult = DB_query("SELECT itemcode, descrip, averagestock, partperunit, sellingprice FROM stockmaster WHERE COALESCE(inactive,0)=0 AND COALESCE(isstock,'1')='1' ORDER BY descrip", $db);
while ($stock = DB_fetch_array($stockResult)) {
    $code = PricingEscape($stock['itemcode']);
    $parts = (float)$stock['partperunit'];
    if ($parts <= 0) {
        $parts = 1;
    }
    $averageCost = (float)$stock['averagestock'];
    $baseCostPerLoose = $averageCost / $parts;
    $freightSql = "SELECT
        COALESCE(SUM(CASE WHEN delivery_totals.total_value > 0 THEN COALESCE(ph.freight,0) * line_values.line_value / delivery_totals.total_value ELSE 0 END),0) AS allocated_freight,
        COALESCE(SUM(line_values.loose_quantity),0) AS received_loose
        FROM (
            SELECT stockledger.invref, stockledger.itemcode,
                   SUM(COALESCE(stockledger.price,0) * (COALESCE(stockledger.fulqty,0) * COALESCE(stockmaster.partperunit,1) + COALESCE(stockledger.loosqty,0))) AS line_value,
                   SUM(COALESCE(stockledger.fulqty,0) * COALESCE(stockmaster.partperunit,1) + COALESCE(stockledger.loosqty,0)) AS loose_quantity
            FROM stockledger
            INNER JOIN stockmaster ON stockmaster.itemcode=stockledger.itemcode
            WHERE stockledger.doctyp='30'
            GROUP BY stockledger.invref, stockledger.itemcode
        ) line_values
        INNER JOIN purchaseheader ph ON ph.documentno=line_values.invref AND ph.documenttype='30'
        INNER JOIN (
            SELECT invref,
                   SUM(COALESCE(price,0) * (COALESCE(fulqty,0) * COALESCE(partperunit,1) + COALESCE(loosqty,0))) AS total_value
            FROM stockledger
            INNER JOIN stockmaster ON stockmaster.itemcode=stockledger.itemcode
            WHERE doctyp='30'
            GROUP BY invref
        ) delivery_totals ON delivery_totals.invref=line_values.invref
        WHERE sl.doctyp='30' AND sl.itemcode='".$code."'";
    $freightResult = DB_query($freightSql, $db);
    $freightRow = DB_fetch_array($freightResult);
    $freightPerLoose = 0;
    if ($freightRow && (float)$freightRow['received_loose'] > 0) {
        $freightPerLoose = (float)$freightRow['allocated_freight'] / (float)$freightRow['received_loose'];
    }
    $landedCost = $baseCostPerLoose + $freightPerLoose;
    $calculated = 0;
    if ($landedCost > 0) {
        $calculated = ($mode === 'margin') ? $landedCost / (1 - ($rate / 100)) : $landedCost * (1 + ($rate / 100));
    }
    echo '<tr><td><input type="checkbox" name="itemcode[]" value="'.htmlspecialchars($stock['itemcode'], ENT_QUOTES, 'UTF-8').'" /></td>';
    echo '<td>'.htmlspecialchars($stock['itemcode'], ENT_QUOTES, 'UTF-8').'</td>';
    echo '<td>'.htmlspecialchars($stock['descrip'], ENT_QUOTES, 'UTF-8').'</td>';
    echo '<td class="number">'.number_format($averageCost, 2).'</td>';
    echo '<td class="number">'.number_format($parts, 2).'</td>';
    echo '<td class="number">'.number_format($freightPerLoose, 2).'</td>';
    echo '<td class="number">'.number_format($landedCost, 2).'</td>';
    echo '<td class="number">'.number_format((float)$stock['sellingprice'], 2).'</td>';
    echo '<td class="number">'.number_format($calculated, 2).'</td></tr>';
}
echo '</tbody></table></div>';
echo '<button class="btn btn-primary" type="submit" name="apply_prices" value="1">'._('Apply Selected Prices').'</button>';
echo '</div></div></form>';

include('includes/footer.inc');
?>