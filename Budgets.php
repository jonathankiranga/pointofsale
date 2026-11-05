<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Setting Up Budgets');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
include('includes/DimensionClass.inc');

   
$FR = new FinancialPeriods();
 
echo '<p class="page_title_text">'
. '<img src="css/'.$Theme.'/images/maintenance.png" title="' . _('Dimension Budgets') .'" alt="" />'
. ' ' . _('Dimension Budgets') . '</p>';

if(isset($_GET['periodno'])){
    $period=$_GET['periodno'];
} 
    
if(isset($_POST['Financial_Periods'])){
    $period=$_POST['Financial_Periods'];
}

if(isset($_POST['submit'])){
    if(isset($_POST['editbudget'])){
        updatebudget();
    }else{
        createbudget();
    }
}

 

echo '<form action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post">';
echo '<div class="card">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if(isset($period)){
    $_POST['Financial_Periods']=$period;
    echo '<input type="hidden" name="editbudget" value="TRUE" />';
    echo '<input type="hidden" name="fperiod" value="'.$period.'" />';
}

echo '<table class="table table-bordered"><tr><th>Budget Period</th><th></th><th></th></tr>';
$FR->Get();
echo '<tr><td colspan="2">'
        . '<table class="table table-bordered"><tr>'
        . '<th>Dimension Name</th>'
        . '<th class="number">BUDGET</th></tr>';

$Result = DB_query("select Dimensions.Code,Dimensions.Dimension from Dimensions join companies on 
                    (Dimensions.id=companies.DefaultDimension_1 or Dimensions.id=DefaultDimension_2) 
                    and [companies].[coycode]=1",$db);

while($rows = DB_fetch_array($Result)){
    
    if(isset($_GET['periodno']) or isset($_POST['Financial_Periods'])){
       $Bamount= Getamount($rows['Code']);
    }else{
       $Bamount= $_POST['amount'][$rows['Code']];
    }
    
    echo sprintf('<tr><td><input type="text" value="%s" readonly="readonly" /></td>'
            . '<td><input type="text" class="number" name="amount[%s]" value="'.$Bamount.'"/></td></tr>',
            $rows['Dimension'],$rows['Code'],$rows['Code'] );
}

echo '<tr><td><input type="submit" name="refresh" value="Refresh"/></td>'
. '<td><input type="submit" name="submit" value="Save Budget"/></td>'
        . '</tr></table></td>';

$Result = DB_query("select distinct
	budgets.periodno,
	max(FinancialPeriods.[end_date]) as ENDDATE,
	min(FinancialPeriods.[start_date]) as STARTDATE,
	(select sum(B.amount) from Budgets B where B.periodno=budgets.periodno) as BUDGET
	from budgets  join FinancialPeriods on budgets.periodno=FinancialPeriods.[periodno]
	group by budgets.periodno,
	Budgets.amount",$db);

echo '<td  valign="top"><table class="table table-bordered"><tr><th>Existing <br/> Budgets by  <br/>Financial Period</th><th class="number">Total Budget</th></tr>';
$thispage=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
while($rows = DB_fetch_array($Result)){
    echo sprintf('<tr><td><a href="%s">%s</a></td><td><input type="text" class="number" value="%s" readonly="readonly" /></td></tr>',
            ($thispage.'?periodno='.$rows['periodno']), $rows['ENDDATE'], number_format($rows['BUDGET'],2) );
}

echo '</table></td></tr>';
echo '</table>';

echo '</div></form>' ;


include('includes/footer.inc');


function Getamount($dim){
    global $db;
    if(isset($_GET['periodno'])){
        $period=$_GET['periodno'];
    } 
    
    if(isset($_POST['Financial_Periods'])){
         $period=$_POST['Financial_Periods'];
    }
    
    
  $SQL="SELECT  
        [periodno]
       ,[dimecode]
       ,[amount]
    FROM [Budgets] where 
    [periodno]='". $period."' and [dimecode]='".$dim."'";
    
    $ResultIndex=DB_query($SQL, $db);
    $rows = DB_fetch_row($ResultIndex);
    
    return $rows[2];
}

function createbudget(){
    Global $db;
    $sql=array();
    
    foreach ($_POST['amount'] as $dimcode => $budget) {
        if($budget>0){
         $sql[]="insert into [Budgets] ([periodno],[dimecode],[amount]) "
        . " values ('".$_POST['Financial_Periods']."','".$dimcode."','".$budget."')";
         
        }
    }
   
    DB_Txn_Begin($db);
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
    }else{
       DB_Txn_Commit($db);
       unset($_POST);
    }
}

function updatebudget(){
   Global $db;
    $sql=array();
    $sql[]="Delete from [Budgets] where [periodno]='".$_POST['fperiod']."'";
    
    foreach ($_POST['amount'] as $dimcode => $budget) {
        if($budget>0){
            $sql[]="insert into [Budgets] ([periodno],[dimecode],[amount]) "
          . " values ('".$_POST['fperiod']."','".$dimcode."','".$budget."')";
        }
    }
   
    DB_Txn_Begin($db);
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
    }else{
       DB_Txn_Commit($db);
       unset($_POST);
    }
}
    
?>