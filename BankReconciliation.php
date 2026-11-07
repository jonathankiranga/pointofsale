<?php
 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');

$Title = _('Bank Reconciliation');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/budgetbalance.php');
include('includes/chartbalancing.inc');

Global $statementstartdate, $DRamount, $CRamount,$tableObject, $PostingGroup,$Uncleared;
$bankReconSequenceLockAcquired = false;

$_POST['date'] = isset($_POST['date']) ? trim($_POST['date']) : '';

$DRamount=0; $CRamount=0; $Openbalance=0; $Difference=0;$Uncleared=0;
$SQL = array() ;
$banklist = array();

$resultindex=DB_query("SELECT `accountcode`,`bankName`,`currency`,`lastreconcileddate`,`AccountNo`,`BranchCode`,`BranchName`,`lastreconbalance`,`lastChequeno`,`PostingGroup`   FROM `BankAccounts`", $db);
while($row=DB_fetch_array($resultindex)){
    $banklist[trim($row['accountcode'])]=$row['bankName'].' '.$row['BranchName'].' '.$row['currency'];
}

if (!isset($_SESSION['newreconciliation'])) {
    $_SESSION['newreconciliation'] = date('U');
}

if(isset($_POST['SaveRecon'])){

    $validStatementNo = isset($_POST['bankstno']) &&
        trim((string)$_POST['bankstno']) !== '' &&
        ctype_digit(trim((string)$_POST['bankstno'])) &&
        strlen(trim((string)$_POST['bankstno'])) <= 10;
    $validStatementDate = isset($_POST['date']) && Is_date($_POST['date']);
    $validBank = isset($_POST['Bank_Code']) &&
        trim((string)$_POST['Bank_Code']) !== '' &&
        isset($banklist[trim((string)$_POST['Bank_Code'])]);
    $validStatementBalance = isset($_POST['bankendbalance']) &&
        trim((string)$_POST['bankendbalance']) !== '' &&
        is_numeric($_POST['bankendbalance']) &&
        is_finite((float)$_POST['bankendbalance']);
    $validFormToken = isset($_POST['FormID'], $_SESSION['FormID']) &&
        hash_equals((string)$_SESSION['FormID'], (string)$_POST['FormID']);
    $validPageToken = isset($_POST['newreconciliation'], $_SESSION['newreconciliation']) &&
        (string)$_POST['newreconciliation'] === (string)$_SESSION['newreconciliation'];

    if (!$validStatementNo) {
        prnMsg('Enter a valid numeric bank statement number (maximum 10 digits).','warn');
    } elseif (!$validStatementDate) {
        prnMsg('You need to select a valid bank statement End Date','warn');
    } elseif (!$validBank) {
        prnMsg('Select a valid bank account before saving the reconciliation','warn');
    } elseif (!$validStatementBalance) {
        prnMsg('Enter a valid bank statement closing balance','warn');
    } elseif (!$validFormToken) {
        prnMsg('Your security token has expired. Reload the reconciliation page.','warn');
    } elseif (!$validPageToken) {
        prnMsg('You cannot resend this page. Reload the reconciliation before saving.','warn');
    } else {
        try {
            if (!$db->begin_transaction()) {
                throw new Exception('Could not start the bank reconciliation transaction: ' . $db->error);
            }

            $bankCode = trim((string)$_POST['Bank_Code']);
            $bankStmt = $db->prepare("SELECT PostingGroup,lastreconcileddate,lastreconbalance,StatementNo
                FROM BankAccounts WHERE accountcode=? LIMIT 1 FOR UPDATE");
            if (!$bankStmt) {
                throw new Exception('Could not lock the selected bank account: ' . $db->error);
            }
            $bankStmt->bind_param('s', $bankCode);
            if (!$bankStmt->execute()) {
                throw new Exception('Could not read the selected bank account: ' . $bankStmt->error);
            }
            $bankRow = $bankStmt->get_result()->fetch_assoc();
            $bankStmt->close();

            if (!$bankRow || trim((string)$bankRow['PostingGroup']) === '') {
                throw new Exception('The selected bank account has no valid posting group.');
            }
            $currentStatementNo = trim((string)$bankRow['StatementNo']);
            if ($currentStatementNo !== '' &&
                (int)$_POST['bankstno'] !== (int)$currentStatementNo) {
                throw new Exception('The bank reconciliation number has changed since this page was loaded. Reload the bank account before saving.');
            }
            if ($bankRow['lastreconbalance'] === null ||
                trim((string)$bankRow['lastreconbalance']) === '' ||
                !is_numeric($bankRow['lastreconbalance']) ||
                !is_finite((float)$bankRow['lastreconbalance'])) {
                throw new Exception('The bank account opening reconciliation balance is NULL or invalid. Set the correct opening balance before reconciling.');
            }

            $endDateSQL = FormatDateForSQL($_POST['date']);
            if ($bankRow['lastreconcileddate'] !== null &&
                trim((string)$bankRow['lastreconcileddate']) !== '' &&
                $bankRow['lastreconcileddate'] !== '0000-00-00' &&
                strtotime($endDateSQL) <= strtotime($bankRow['lastreconcileddate'])) {
                throw new Exception('The statement end date must be later than the last reconciled date.');
            }

            $PostingGroup = trim((string)$bankRow['PostingGroup']);
            $statementstartdate = $bankRow['lastreconcileddate'];
            $openingBalance = (float)$bankRow['lastreconbalance'];

            // Recalculate the closing balance from locked BankTransactions rows.
            // The browser's Totalcleared field is display-only and is not trusted.
            $serverClearedBalance = CalculatePostedReconciliationBalance(
                $bankCode,
                $openingBalance,
                $endDateSQL
            );
            if (abs(round($serverClearedBalance, 2) - round((float)$_POST['bankendbalance'], 2)) >= 0.005) {
                throw new Exception('The statement closing balance does not match the selected cashbook transactions. No reconciliation changes were saved.');
            }

            if (!UpdateStatus()) {
                throw new Exception('Could not update the cleared status of all selected bank transactions.');
            }
            if (!SaveBankreconciliation()) {
                throw new Exception('Could not save the reconciliation summary and bank account balance.');
            }
            if (!$db->commit()) {
                throw new Exception('Could not commit the bank reconciliation: ' . $db->error);
            }
            if ($bankReconSequenceLockAcquired) {
                $db->query("SELECT RELEASE_LOCK('bankreconciliation_pkey')");
                $bankReconSequenceLockAcquired = false;
            }

            $_SESSION['newreconciliation'] = date('U');
            $_SESSION['Printreconciliation'] = array();
            $_GET['new'] = '1';
            prnMsg('Bank reconciliation saved successfully.','success');
        } catch (Throwable $e) {
            $db->rollback();
            if ($bankReconSequenceLockAcquired) {
                $db->query("SELECT RELEASE_LOCK('bankreconciliation_pkey')");
                $bankReconSequenceLockAcquired = false;
            }
            error_log('Bank reconciliation save failed: ' . $e->getMessage());
            prnMsg($e->getMessage(),'warn');
        }
    }

}

echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/customer.png" title="' . _('Bank Reconciliation') .'" alt="" />'. _('Bank Reconciliation') . '</p>';
echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div class="card">';
echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>';

if(isset($_GET['new'])){
    $_SESSION['newreconciliation']=date('U');
    $_SESSION['Printreconciliation']=array();
}

if(isset($_POST['bankstno']) and isset($_POST['Bank_Code'])){
    echo '<a href="PDFbankreconciliation.php?id='.trim($_POST['bankstno']).'&bankcode='.$_POST['Bank_Code'].'">PRINT RECONCILIATION</a>';
}
   
echo '<input type="hidden" name="newreconciliation" value="'.$_SESSION['newreconciliation'].'"/>';
echo '<table class="table-bordered bank-reconciliation-form">';
echo  constructBanks(); 

if(isset($_POST['Bank_Code']) && trim($_POST['Bank_Code']) !== ''){
    $TRBanks = new ShowBankAccounts();
    $ArrayBanks = $TRBanks->GetBankDetails($_POST['Bank_Code']);

If(!isset($_POST['bankstno'])){ 

    if(mb_strlen($ArrayBanks['StatementNo'])>0){
       Echo '<tr><td>Bank Recon No:</td><td><input tabindex="1" type="text" class="number" name="bankstno" size="11" maxlength="10" readonly="readonly"  value="'. $ArrayBanks['StatementNo'] .'"/></td></tr>';
    } else {
       Echo '<tr><td>Bank Recon No:</td><td><input tabindex="1" type="text" class="number"  name="bankstno" size="11" maxlength="10"   value="'. $_POST['bankstno'] .'"/></td></tr>';
    }

} else {
    Echo '<tr><td>Bank Recon No:</td><td><input tabindex="1" type="text" class="number"  name="bankstno" size="11" maxlength="10"   value="'. $_POST['bankstno'] .'"/></td></tr>';
}
    
$PostingGroup       = $ArrayBanks['PostingGroup'];
$statementstartdate = $ArrayBanks['lastreconcileddate'];
$Openbalance        = $ArrayBanks['lastreconbalance'];

 $ReconciliationEndDate = Is_date($_POST['date']) ? FormatDateForSQL($_POST['date']) : '';
 echo '<input type="hidden" id="statementstartdate" value="' . $statementstartdate . '"/><input type="hidden" id="bankrecondate_sql" value="' . $ReconciliationEndDate . '"/>';
   
if(!isset($_POST['bankendbalance'])){
    $_POST['bankendbalance']=$ArrayBanks['lastreconbalance'];
}
   
Echo '<tr><td>Statement Start Date:</td><td><input tabindex="2" type="text" class="number"  name="statementstartdate" size="11" maxlength="10" readonly="readonly" value="' . ConvertSQLDate($ArrayBanks['lastreconcileddate']). '"/></td></tr>';
Echo '<tr><td>Statement Start Balance:</td><td><input tabindex="2" type="text" class="number" id="lastreconbalance"  name="bankstartbalance" size="11" maxlength="10" readonly="readonly" value="' .$ArrayBanks['lastreconbalance']. '"/></td></tr>';
Echo '<tr><td colspan="2"><input type="hidden" class="number" name="Cashbookstartbalance" size="11" maxlength="10" readonly="readonly" value="' .GetClearedDB(). '"/></td></tr>';
echo '<tr><td>Statement End Date</td><td><input tabindex="3" type="text" class="date" id="bankrecondate" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10"  required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
echo '<tr><td>Statement End Balance</td><td><input tabindex="4" type="text" class="number"  id="bankendbalance" name="bankendbalance" size="11" maxlength="10"  value="' .$_POST['bankendbalance']. '"   onchange="GetAllCleared(this.value)"/></td></tr>';

echo '<tr><td><B>Cleared Debit Balance:</B></td>'
     . '<td><input type="text" class="number" id="clearedDB" name="TotalDebit" value=""  readonly="readonly"/></td>';

echo '<td><B>Cleared Total Balance:</B></td>'
      . '<td><input type="text" class="number" id="cleared" name="Totalcleared" value=""  readonly="readonly"/>'
      . '</td></tr>';

echo '<tr><td><B>Cleared Credit Balance:</B></td>'
     . '<td><input type="text" class="number" id="clearedCR" name="TotalCredit" value="" readonly="readonly"/></td>'
     . '<td><B>Diff(end balance-cleared):</B></td>'
     . '<td><input type="text" class="number" id="Uncleared" name="Uncleared" value="" readonly="readonly"/></td>';

echo '</tr></table>';
    
 
}
 
echo '<span id="loadbankdata"></span>';

echo '</div></form>' ;

echo '<script type="text/javascript">
function bankReconRound2(value) {
    value = Number(value);
    return isFinite(value) ? Math.round(value * 100) / 100 : 0;
}
function updateBankReconBalance() {
    var opening = Number(document.getElementById("lastreconbalance").value || 0);
    var debit = Number(document.getElementById("clearedDB").value || 0);
    var credit = Number(document.getElementById("clearedCR").value || 0);
    var statement = Number(document.getElementById("bankendbalance").value || 0);
    var total = bankReconRound2(opening + debit + credit);
    var difference = bankReconRound2(statement - total);
    document.getElementById("cleared").value = total.toFixed(2);
    document.getElementById("Uncleared").value = difference.toFixed(2);
}
function GetDBCleared(amount, checkbox) {
    var current = Number(document.getElementById("clearedDB").value || 0);
    var delta = Number(amount);
    if (!isFinite(delta)) { return; }
    current += checkbox.checked ? delta : -delta;
    document.getElementById("clearedDB").value = bankReconRound2(current).toFixed(2);
    updateBankReconBalance();
}
function GetCRCleared(amount, checkbox) {
    var current = Number(document.getElementById("clearedCR").value || 0);
    var delta = Number(amount);
    if (!isFinite(delta)) { return; }
    current += checkbox.checked ? delta : -delta;
    document.getElementById("clearedCR").value = bankReconRound2(current).toFixed(2);
    updateBankReconBalance();
}
function GetAllCleared(value) {
    updateBankReconBalance();
}
</script>';

include('includes/footer.inc');

Function UpdateStatus(){
    Global $db;

    if (!isset($_POST['Cheq']) || !is_array($_POST['Cheq'])) {
        return true;
    }

    $bankCode = trim((string)$_POST['Bank_Code']);
    $clearStmt = $db->prepare("UPDATE BankTransactions SET cleared=1
        WHERE journal=? AND bankcode=? AND reconciled IS NULL");
    $unclearStmt = $db->prepare("UPDATE BankTransactions SET cleared=NULL
        WHERE journal=? AND bankcode=? AND reconciled IS NULL");
    if (!$clearStmt || !$unclearStmt) {
        if ($clearStmt) { $clearStmt->close(); }
        if ($unclearStmt) { $unclearStmt->close(); }
        return false;
    }

    foreach ($_POST['Cheq'] as $journal => $value) {
        $journal = trim((string)$journal);
        if ($journal === '') {
            $clearStmt->close();
            $unclearStmt->close();
            return false;
        }
        $selected = (isset($_POST['check']) && is_array($_POST['check']) &&
                     isset($_POST['check'][$journal]) && $_POST['check'][$journal] === 'on') ||
                    (isset($_POST['check2']) && is_array($_POST['check2']) &&
                     isset($_POST['check2'][$journal]) && $_POST['check2'][$journal] === 'on');
        $stmt = $selected ? $clearStmt : $unclearStmt;
        $stmt->bind_param('ss', $journal, $bankCode);
        if (!$stmt->execute()) {
            $clearStmt->close();
            $unclearStmt->close();
            return false;
        }
    }

    $clearStmt->close();
    $unclearStmt->close();
    return true;
}

function constructBanks() {
   Global $db,$banklist;

    $BankCodeSelected = isset($_POST['Bank_Code']) && trim($_POST['Bank_Code']) !== '';
    $banknames = $BankCodeSelected && isset($banklist[trim($_POST['Bank_Code'])])
        ? $banklist[trim($_POST['Bank_Code'])]
        : '';

    if($BankCodeSelected){
        
        $BankObject='<tr><td><input type="hidden" name="GetBankData" value="Accept Selection"/>Select Bank:</td>'
                      . '<td><input type="text" id="bankselected" name="Bank_Code" readonly="readonly" value="'.$_POST['Bank_Code'].'" /></td>'
                       . '<td>'. $banknames.'</td><td><input type="button" id="GetBankData" value="load Bank Data"/></td></tr>';
 
        echo  $BankObject;
        
    } else{    
        
        $BankObject='<tr><td>Select Bank:</td>'
                . '<td colspan="3"><Select id="bankselected" name="Bank_Code" required="required">';
        $resultindex=DB_query("SELECT `accountcode`,`bankName`,`currency`,"
                . "`lastreconcileddate`,`AccountNo`,`BranchCode`,`BranchName`,"
                . "`lastreconbalance`,`lastChequeno`,`PostingGroup` "
                . " FROM `BankAccounts`", $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code'])){
                $BankObject .= '<option value="'.$row['accountcode'].'"  '.((trim($_POST['Bank_Code'])==trim($row['accountcode']))?'selected="selected"':'').'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }else{
                $BankObject .= '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }
       }
        
        $BankObject .='</select>';
        $BankObject .= '<input type="submit" name="GetBankData" value="Accept Selection"/>';
        $BankObject .= '<br/><em>Click and Please wait for the transactions to load</em></td></tr>';
        
        echo $BankObject;
    
     }
}

function CreateRow($D,$N,$M,$M2,$A,$Journal,$cleared){
    Global $DRamount,$Uncleared;

    // Rendering rows is read-only. Missing checkboxes on a submitted form
    // represent an explicit unchecked selection.
    $hasPostedRows = isset($_POST['Cheq']) && is_array($_POST['Cheq']);
    $isChecked = $hasPostedRows
        ? (isset($_POST['check']) && is_array($_POST['check']) &&
           isset($_POST['check'][$Journal]) && $_POST['check'][$Journal] === 'on')
        : ((int)$cleared === 1);
    $currentStatus = $isChecked ? 'checked="checked"' : 'unchecked="unchecked"';

    if($isChecked){
        $DRamount += (float)$A;
    }else{
        $Uncleared += (float)$A;
    }

    return SprintF('<tr>'
            . '<td>%s</td>'
            . '<td>%s<input type="hidden" name="Cheq[%s]" value="%s"/></td>'
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td class="number"><input type="text" class="number" name="Amount[%s]" value="%s" size="10" readonly="readonly"/></td>'
            . '<td><input type="checkbox" name="check[%s]" %s onchange="GetDBCleared(%s,this)"/></td>'
            . '</tr>',$D,$N,$Journal,$N,$M,$M2,$Journal,number_format(abs($A),2),$Journal,$currentStatus,$A);
}

function CreateRow2($D,$N,$M,$M2,$A,$Journal,$cleared){
    Global $CRamount,$Uncleared;

    $hasPostedRows = isset($_POST['Cheq']) && is_array($_POST['Cheq']);
    $isChecked = $hasPostedRows
        ? (isset($_POST['check2']) && is_array($_POST['check2']) &&
           isset($_POST['check2'][$Journal]) && $_POST['check2'][$Journal] === 'on')
        : ((int)$cleared === 1);
    $currentStatus = $isChecked ? 'checked="checked"' : 'unchecked="unchecked"';

    if($isChecked){
        $CRamount += (float)$A;
    }else{
        $Uncleared += (float)$A;
    }

    return SprintF('<tr>'
            . '<td>%s</td>'
            . '<td>%s<input type="hidden" name="Cheq[%s]" value="%s"/></td>'
            . '<td>%s</td>'
            . '<td>%s</td>'
            . '<td class="number"><input type="text" class="number" name="Amount[%s]" value="%s" size="10" readonly="readonly"/></td>'
            . '<td><input type="checkbox" name="check2[%s]" %s onchange="GetCRCleared(%s,this)"/></td>'
            . '</tr>', $D,$N,$Journal,$N,$M,$M2,$Journal,number_format(abs($A),2),$Journal,$currentStatus,$A);
}

function Insert($pkey,$StatementNo,$bankcode,$narration,$amount=''){
    global $db;

    $pkey = (int)$pkey;
    if ($pkey < 1) {
        throw new Exception('Could not allocate a valid bank reconciliation detail ID.');
    }
    $StatementNo = mysqli_real_escape_string($db, (string)$StatementNo);
    $bankcode = mysqli_real_escape_string($db, (string)$bankcode);
    $narration = mysqli_real_escape_string($db, (string)$narration);

    if ($amount !== '' && is_numeric($amount) && is_finite((float)$amount)) {
        return sprintf("INSERT INTO BankReconciliation (pkey,StatementNo,bankcode,narration,amount)
            VALUES (%d,'%s','%s','%s',%.2f)", $pkey, $StatementNo, $bankcode, $narration, (float)$amount);
    }

    return sprintf("INSERT INTO BankReconciliation (pkey,StatementNo,bankcode,narration)
        VALUES (%d,'%s','%s','%s')", $pkey, $StatementNo, $bankcode, $narration);
}

function IsReconciliationJournalSelected($journal){
    return (isset($_POST['check']) && is_array($_POST['check']) &&
            isset($_POST['check'][$journal]) && $_POST['check'][$journal] === 'on') ||
           (isset($_POST['check2']) && is_array($_POST['check2']) &&
            isset($_POST['check2'][$journal]) && $_POST['check2'][$journal] === 'on');
}

function CalculatePostedReconciliationBalance($bankCode,$openingBalance,$endDateSQL){
    global $db;

    $postedRows = (isset($_POST['Cheq']) && is_array($_POST['Cheq'])) ? $_POST['Cheq'] : array();
    $countStmt = $db->prepare("SELECT COUNT(*) AS row_count,
            COALESCE(SUM(CASE WHEN amount IS NULL OR DocDate IS NULL THEN 1 ELSE 0 END),0) AS invalid_count
        FROM BankTransactions
        WHERE bankcode=? AND reconciled IS NULL
          AND (DocDate IS NULL OR DocDate<=?)");
    if (!$countStmt) {
        throw new Exception('Could not verify the complete set of unreconciled bank transactions: ' . $db->error);
    }
    $endDateTime = $endDateSQL . ' 23:59:59';
    $countStmt->bind_param('ss', $bankCode, $endDateTime);
    if (!$countStmt->execute()) {
        $error = $countStmt->error;
        $countStmt->close();
        throw new Exception('Could not verify the complete set of unreconciled bank transactions: ' . $error);
    }
    $countRow = $countStmt->get_result()->fetch_assoc();
    $countStmt->close();
    if (!$countRow || (int)$countRow['invalid_count'] > 0) {
        throw new Exception('An unreconciled bank transaction contains a NULL amount or transaction date. Correct it before reconciling.');
    }
    if ((int)$countRow['row_count'] !== count($postedRows)) {
        throw new Exception('The reconciliation list is incomplete or out of date. Reload the bank transactions before saving.');
    }
    if (!$postedRows) {
        return round((float)$openingBalance, 2);
    }

    $stmt = $db->prepare("SELECT amount,DocDate,reconciled FROM BankTransactions
        WHERE journal=? AND bankcode=? FOR UPDATE");
    if (!$stmt) {
        throw new Exception('Could not prepare bank transaction verification: ' . $db->error);
    }

    $balance = (float)$openingBalance;
    foreach ($postedRows as $journal => $documentNo) {
        $journal = trim((string)$journal);
        if ($journal === '') {
            $stmt->close();
            throw new Exception('A bank transaction has an empty journal number.');
        }

        $stmt->bind_param('ss', $journal, $bankCode);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception('Could not verify a selected bank transaction: ' . $error);
        }
        $result = $stmt->get_result();
        if ($result->num_rows !== 1) {
            $stmt->close();
            throw new Exception('A displayed bank transaction is missing or has an ambiguous journal number. Refresh the reconciliation before saving.');
        }
        $row = $result->fetch_assoc();
        if ($row['reconciled'] !== null && trim((string)$row['reconciled']) !== '') {
            $stmt->close();
            throw new Exception('A transaction in this statement has already been reconciled. Refresh the reconciliation.');
        }
        if ($row['amount'] === null || !is_numeric($row['amount']) ||
            !is_finite((float)$row['amount']) || $row['DocDate'] === null ||
            strtotime($row['DocDate']) === false || strtotime($row['DocDate']) > strtotime($endDateSQL . ' 23:59:59')) {
            $stmt->close();
            throw new Exception('A bank transaction contains a NULL or invalid amount/date, or falls after the statement end date.');
        }

        if (IsReconciliationJournalSelected($journal)) {
            $balance += (float)$row['amount'];
        }
    }

    $stmt->close();
    return round($balance, 2);
}

function SaveBankreconciliation(){
    global $db;

    $bankCode = trim((string)$_POST['Bank_Code']);
    $statementNo = (int)$_POST['bankstno'] + 1;
    $statementDate = FormatDateForSQL($_POST['date']);
    $statementBalance = round((float)$_POST['bankendbalance'], 2);

    if (isset($_POST['Cheq']) && is_array($_POST['Cheq'])) {
        $stmt = $db->prepare("UPDATE BankTransactions SET reconciled='R'
            WHERE journal=? AND bankcode=? AND reconciled IS NULL");
        if (!$stmt) { return false; }

        foreach ($_POST['Cheq'] as $journal => $documentNo) {
            $journal = trim((string)$journal);
            if (IsReconciliationJournalSelected($journal)) {
                $stmt->bind_param('ss', $journal, $bankCode);
                if (!$stmt->execute() || $stmt->affected_rows !== 1) {
                    $stmt->close();
                    return false;
                }
            }
        }
        $stmt->close();
    }

    $bankUpdate = $db->prepare("UPDATE BankAccounts
        SET StatementNo=?,lastreconcileddate=?,lastreconbalance=?
        WHERE accountcode=?");
    if (!$bankUpdate) { return false; }
    $bankUpdate->bind_param('isds', $statementNo, $statementDate, $statementBalance, $bankCode);
    if (!$bankUpdate->execute() || $bankUpdate->affected_rows !== 1) {
        $bankUpdate->close();
        return false;
    }
    $bankUpdate->close();

    return UpdateCashbook();
}

function UpdateCashbook(){
    global $db,$bankReconSequenceLockAcquired;

    $bankCode = trim((string)$_POST['Bank_Code']);
    $statementNo = trim((string)$_POST['bankstno']);
    $statementDate = FormatDateForSQL($_POST['date']);
    $statementBalance = round((float)$_POST['bankendbalance'], 2);

    $date = array();
    $date2 = array();
    $amount = array();
    $amount2 = array();
    $narration = array();
    $narration2 = array();
    $totalDebit = 0.00;
    $totalCredit = 0.00;

    $stmt = $db->prepare("SELECT DocDate,amount,journal,
            COALESCE(NULLIF(TRIM(narrative),''),CONCAT('Journal ',journal)) AS narrative
        FROM BankTransactions
        WHERE bankcode=? AND cleared IS NULL AND reconciled IS NULL AND DocDate<=?");
    if (!$stmt) { return false; }
    $statementEndDateTime = $statementDate . ' 23:59:59';
    $stmt->bind_param('ss', $bankCode, $statementEndDateTime);
    if (!$stmt->execute()) { $stmt->close(); return false; }
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if ($row['amount'] === null || !is_numeric($row['amount']) ||
            !is_finite((float)$row['amount']) || $row['DocDate'] === null) {
            $stmt->close();
            return false;
        }
        $journal = (string)$row['journal'];
        if ((float)$row['amount'] > 0) {
            $date[$journal] = $row['DocDate'];
            $amount[$journal] = abs((float)$row['amount']);
            $narration[$journal] = trim((string)$row['narrative']);
            $totalDebit += (float)$row['amount'];
        } elseif ((float)$row['amount'] < 0) {
            $date2[$journal] = $row['DocDate'];
            $amount2[$journal] = abs((float)$row['amount']);
            $narration2[$journal] = trim((string)$row['narrative']);
            $totalCredit += (float)$row['amount'];
        }
    }
    $stmt->close();

    $lockResult = $db->query("SELECT GET_LOCK('bankreconciliation_pkey',10) AS lock_ok");
    $lockRow = $lockResult ? $lockResult->fetch_assoc() : null;
    if (!$lockRow || (int)$lockRow['lock_ok'] !== 1) {
        return false;
    }
    $bankReconSequenceLockAcquired = true;

    $maxResult = $db->query("SELECT COALESCE(MAX(pkey),0) AS max_id FROM BankReconciliation");
    $maxRow = $maxResult ? $maxResult->fetch_assoc() : null;
    if (!$maxRow || !is_numeric($maxRow['max_id'])) {
        return false;
    }
    $nextPkey = (int)$maxRow['max_id'] + 1;

    $sql = array();
    $sql[] = Insert($nextPkey++,$statementNo,$bankCode,'Reconciliation As at '.$_POST['date']);
    $sql[] = Insert($nextPkey++,$statementNo,$bankCode,'Bank Statement Balance',$statementBalance);
    $sql[] = Insert($nextPkey++,$statementNo,$bankCode,'ADD: Uncredited Cheques');
    foreach ($date as $journal => $docDate) {
        $sql[] = Insert($nextPkey++,$statementNo,$bankCode,$date[$journal].' '.$narration[$journal],$amount[$journal]);
    }
    $sql[] = Insert($nextPkey++,$statementNo,$bankCode,'Total Uncredited Cheques',$totalDebit);
    $sql[] = Insert($nextPkey++,$statementNo,$bankCode,'LESS: Unpresented Cheques');
    foreach ($date2 as $journal => $docDate) {
        $sql[] = Insert($nextPkey++,$statementNo,$bankCode,$date2[$journal].' '.$narration2[$journal],$amount2[$journal]);
    }
    $sql[] = Insert($nextPkey++,$statementNo,$bankCode,'Total Unpresented Cheques',$totalCredit);
    $sql[] = Insert($nextPkey++,$statementNo,$bankCode,'Balance as Per Cash Book',
        $statementBalance + $totalDebit + $totalCredit);

    foreach ($sql as $query) {
        $result = DB_query($query,$db,'','',false,false);
        if (!$result || $db->errno !== 0 || $db->affected_rows !== 1) {
            return false;
        }
    }

    return true;
}

Function GetClearedDB(){
    global $db,$statementstartdate,$PostingGroup;

    $SQL = "Select COALESCE(sum(`amount`),0) from `BankTransactions`"
         . " where `bankcode`='" . mysqli_real_escape_string($db, (string)$_POST['Bank_Code']) . "'"
         . " and `cleared`=1";

    // A bank account may not have been reconciled before. In that case
    // lastreconcileddate is NULL and there is no cutoff date to apply.
    $ReconciliationStartDate = trim((string)$statementstartdate);
    if ($ReconciliationStartDate !== '' && $ReconciliationStartDate !== '0000-00-00') {
        $SQL .= " and `DocDate` <= '" . mysqli_real_escape_string($db, $ReconciliationStartDate . ' 23:59:59') . "'";
    }

    $ResultIndex = DB_query($SQL, $db);
    $cashbookbalance = DB_fetch_row($ResultIndex);
    return $cashbookbalance[0];
}

Function GetUnClearedDB(){
    global $db,$statementstartdate,$PostingGroup;

    $SQL = "Select COALESCE(sum(`amount`),0) from `BankTransactions`"
         . " where `bankcode`='" . mysqli_real_escape_string($db, (string)$_POST['Bank_Code']) . "'"
         . " and (`cleared`=0 or `cleared` is null)"
         . " and `reconciled` is null";

    // Apply the same cutoff rule as GetClearedDB().
    $ReconciliationStartDate = trim((string)$statementstartdate);
    if ($ReconciliationStartDate !== '' && $ReconciliationStartDate !== '0000-00-00') {
        $SQL .= " and `DocDate` <= '" . mysqli_real_escape_string($db, $ReconciliationStartDate . ' 23:59:59') . "'";
    }

    $ResultIndex = DB_query($SQL, $db);
    $cashbookbalance = DB_fetch_row($ResultIndex);
    return $cashbookbalance[0];
}

?>
