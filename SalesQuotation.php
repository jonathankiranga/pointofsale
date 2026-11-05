<?php
$PageSecurity=0;
include('includes/session.inc');
include('includes/CurrenciesArray.php');
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
$Title = _('Sample Registration');
include('includes/header.inc');
?><style>
 .form-group {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
 }

 .form-group .col-6 {
    width: 33%; /* Adjust the width as needed */
 }

 .quoteamount{
     width: 100px;
 }
 
 .fieldquote{
    white-space: normal; /* Allows the text to wrap to the next line */
    word-wrap: break-word; /* Breaks long words and wraps to the next line */
    overflow-wrap: break-word; /* Ensures long words are broken and wrapped to the next line */
    width: 100%; /* Ensures the element takes up the full width of its container */
    display: block; /* Ensures the label behaves like a block element */

 }

</style><?php
       
if(isset($_POST['editquote'])){
    $SQL=sprintf("Delete from [SalesLine]  where [documentno]='%s' and [documenttype]='54'",$_POST['documentno']);
    $Result=DB_query($SQL,$db);
    
    $SQL=sprintf("Delete from [SalesHeader]  where [documentno]='%s' and [documenttype]='54'",$_POST['documentno']);
    $Result=DB_query($SQL,$db);
    $_POST['preview']='preview';
}

if(isset($_POST['Delete'])){
    $SQL=sprintf("Delete from [SalesLine]  where [documentno]='%s' and [documenttype]='54'",$_POST['documentno']);
    $Result=DB_query($SQL,$db);
    
    $SQL=sprintf("Delete from [SalesHeader]  where [documentno]='%s' and [documenttype]='54'",$_POST['documentno']);
    $Result=DB_query($SQL,$db);
    
     unset($_SESSION['__FormQuote']);
}

if(isset($_POST['confirm'])){
    $SQL=sprintf("Update [SalesHeader] set [discount]=%f where [documentno]='%s' and [documenttype]='54'",
            $_POST['discount'],$_POST['documentno']);
    $Result=DB_query($SQL,$db);
    unset($_SESSION['__FormQuote']);
     $_SESSION['savedsuccessfuly']=false;
     $_POST['documentno'] = GetTempLabrefNo(54,$db);
}

 $ERROR=0;
If(isset($_POST['preview'])){
    
    if(!is_array($_POST['sampletype'])){
       prnMsg('Please Select Sample Type','info');
       $ERROR++;
    }
    
    if(!is_array($_POST['Pricing'])){
        prnMsg('Please Select the Package offered','info');
        $ERROR++;
    }
    
    if(!isset($_POST['CustomerID'])){
       prnMsg('Please Select the Customer Account','info');
       $ERROR++;
    } 
     
   if($ERROR==0) {
        if($_SESSION['loadedit']==TRUE){
            unset($_SESSION['loadedit']);
        }else{
          $_POST['documentno'] = GetNextLabrefNo(54,$db);
        }
        $filename= 'quotes/'.trim($_POST['documentno']).'.terms';
        file_put_contents($filename,$_POST['paymentterms']."\r\n\r\n",FILE_APPEND);
 
       include('includes/SampleQuote.inc'); 
   }
}
     
 if($_SESSION['savedsuccessfuly']==true){
    echo '<div class="card"><form action="'.$thispage .'" method="post">';
    echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
    echo '<input type="hidden" id="_documentno" name="documentno" value="'. $_POST['documentno'] .'" />';

    $SQL="select 
       [documentno],[docdate],[oderdate] ,[duedate] ,[postingdate] ,[customercode] ,[customername]
      ,[yourreference],[externaldocumentno] ,[locationcode]  ,[paymentterms],[postinggroup]
      ,[currencycode] ,[salespersoncode] ,[printed] ,[released],[status]
      ,[userid] from [SalesHeader] where [documenttype]='54' 
      and  [documentno]='".$_POST['documentno']."'";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table-bordered"><tr><th></th><th></th></tr>';
    $row=DB_fetch_row($Result);
        
    echo sprintf("<tr><td>Sales Quote No</td><td>%s</td></tr>",$_POST['documentno']);
    echo sprintf("<tr><td>Sales Quote Document date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td>Customer ID</td><td>%s</td></tr>", $row[5]);
    echo sprintf("<tr><td>Customer Name</td><td>%s</td></tr>",$row[6]);
    echo '<tr><td colspan="2"></td></tr>';
    
    
    $SQL="select 
            [documenttype] ,[docdate] ,[documentno] ,[code],[description],[Quantity],
            [UnitPrice],[vatamount] ,[invoiceamount] ,UOM ,[Itemlist]
           from [SalesLine] where [documenttype]=54 and [documentno]='".$_POST['documentno']."'";
    echo '<tr><td colspan="2">';
    
    echo '<table class="table-bordered"><thead><tr>'
            . '<th>Parameter</th>'
            . '<th>Itemlist</th>'
            . '<th class="number">Sales Price</th>'
            . '<th class="number">VAT Amount</th>'
            . '<th class="number">Gross Amount</th>'
            . '</tr></thead>';
    
    $VATAMOUNT=0;
    $INVOICEAMOUNT=0;
    
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
         echo sprintf('<tr>'
                 . '<td><label class="fieldquote">%s</label></td>'
                 . '<td><label  class="fieldquote">%s</label></td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td></tr>',
                 $myrow['description'],
                 html_entity_decode($myrow['Itemlist']),
                 number_format($myrow['UnitPrice'],2),
                 number_format($myrow['vatamount'],2),
                 number_format($myrow['invoiceamount'],2));
     
         $VATAMOUNT += $myrow['vatamount'];
         $INVOICEAMOUNT  +=$myrow['invoiceamount'];
    }
    
    if(!isset($_POST['discount'])){
        $_POST['discount']=0;
        $_POST['vatamount']=0;
        $_POST['invoiceamount']=0;
        $_POST['Lvatamount']=$VATAMOUNT;
        $_POST['Linvoiceamount']=$INVOICEAMOUNT ;
    }
  
     echo sprintf('<tr><td></td><td>Sales Quote</td><td></td>'
             . '<td class="number"><input type="text" class="number" id="totalvatamount" name="totalvatamount" value="%s" readonly="readonly"/></td>'
             . '<td class="number"><input type="text" class="number" id="totalinvoiceamount" name="totalinvoiceamount" value="%s" readonly="readonly"/></td></tr></tfoot>',$VATAMOUNT,$INVOICEAMOUNT);
   
     echo '<tr><td></td>'
        . '<td>Discount</td>'
        . '<td class="number"><input type="number" min=0 max=99  id="Qdiscount" size="3" name="discount"  value="'.$_POST['discount'].'"/></td>'
        . '<td class="number"><input type="text" class="number" id="Qvatamount"  name="vatamount"  value="'.$_POST['vatamount']  .'" readonly="readonly"/></td>'
        . '<td class="number"><input type="text" class="number" id="Qinvoiceamount" name="invoiceamount"  value="'.$_POST['invoiceamount'] .'"  readonly="readonly"/></td></tr>';
   
   echo '<tfoot><tr><td></td>'
        . '<td >Net Total</td><td></td>'
        . '<td class="number"><input type="text" class="number" id="Lvatamount"  name="Lvatamount"   value="'.$_POST['Lvatamount'].'"  readonly="readonly"/></td>'
        . '<td class="number"><input type="text" class="number" id="Linvoiceamount"  name="Linvoiceamount"   value="'.$_POST['Linvoiceamount'].'"  readonly="readonly"/></td></tr></tfoot>';

    echo '</table></td></tr>';
    echo '</table>';
    
    echo '<div class="leftside"><input type="submit" name="refresh" value="'._('Refresh').'"/></div>';
    if(isset($_POST['discount'])){
       echo '<div class="rightside"><input type="submit" name="confirm" value="'._('Confirm Sales Quote').'"/>';
    }else{
        echo '<div class="rightside"><input type="submit" name="Reload" value="'._('Confirm Sales Quote').'"/>';
    }
    echo '<input type="submit" name="Delete" value="'._('Cancel Quote').'"/></div>';
    echo '</form></div>';
    $_SESSION['CompleteDocument']=$_POST['documentno']; 

}else{
if(isset($_GET['EditNo'])){
        $_SESSION['loadedit']=TRUE;
        $_SESSION['locked'] = true;
        $_SESSION['savedsuccessfuly']=false;
        $_POST['documentno'] = $_GET['EditNo'];
        
        $SQL="select 
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[oderdate]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,rtrim([salesrepsinfo].[salesman])+' ,Tel:'+IFNULL([salesrepsinfo].[phone],'')
      ,rtrim([salesrepsinfo].[email])
      ,[SalesHeader].[userid]
      ,[debtors].email
      ,[debtors].city
      ,[debtors].postcode
      ,[debtors].country
      ,[debtors].phone
      ,[debtors].contact  
      ,[SalesHeader].[yourreference] as BankCode2
      ,SalesHeader.[locationcode] as BankCode
      ,SalesHeader.paymentterms
      ,SalesHeader.[discount]
      ,SalesHeader.vatinclusive
      from [SalesHeader] 
      join debtors on [SalesHeader].[customercode]=debtors.itemcode
      left join [salesrepsinfo] on [salesrepsinfo].code=[SalesHeader].[salespersoncode]
      where [SalesHeader].[documenttype]='54' 
      and [SalesHeader].[documentno]='".$_GET['EditNo']."'";

    $Result = DB_query($SQL,$db);
    $myrow  = DB_fetch_row($Result);
    $_POST['CustomerID']=trim($myrow[4]);
    $_POST['CustomerName']=trim($myrow[5]);
    $_POST['Bank_Code']  = trim($myrow[17]);
    $_POST['Bank_Code2'] = trim($myrow[16]);
    $_POST['discount'] = (int)($myrow[19]);
    $_POST['terms']=trim($myrow[18]);
    $_POST['paymentterms']=getFooter($_GET['EditNo']);
    prnMsg("Re-Select the Lab parameters, The previous data was summerized and cannot be retrievd as it was input",'inf');
  
}else{
        if(!isset($_POST['documentno'])){
           $_SESSION['locked'] = true;
           $_SESSION['savedsuccessfuly']=false;
           $_POST['documentno'] = GetTempLabrefNo(54,$db);
       }
}

$ResultIndex = DB_query('Select sysdate() as date',$db);
$rowdate = DB_fetch_row($ResultIndex);
 if(!isset($_POST['date'])){
    $_POST['date'] = ConvertSQLDate($rowdate[0]);
}

$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

echo '<div class="centre"><p class="page_title_text"><img src="css/'.$Theme.'/images/sales.png" title="' . _('Laboratory Test Quotation') .'" alt="" />' . ' ' . _('Laboratory Test Quotation') . '</p>';
echo '<div class="card"><form action="'.$thispage .'" method="post" id="labform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
echo '<div class="card"><div class="card-header"><large class="card-title">
<button class="btn btn-primary btn-sm btn-block col-md-2 float-right" type="button" id="new_customer"><i class="fa fa-plus"></i> Create New Customer</button></large>
</div><div class="card-body">';
echo '<div><table class="table-bordered">';
echo '<tr><td>Date:</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus"  value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
echo '<tr><td>Quote No:</td><td><input tabindex="2" type="text" name="documentno" value="'.$_POST['documentno'].'" size="10" readonly="readonly"/></td></td></tr>';
echo '<tr><td><input type="hidden" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  />';
echo 'Client Name :</td><td><input tabindex="4" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  /><div id="dropdown" class="dropdown"></div></td></tr>';
echo '<input type="hidden" id="currencycode" size="5" name="currencycode"  value="'.$_POST['currencycode'].'"/>'
   . '<input type="hidden" name="salespersoncode" id="salespersoncode"/></td></tr>';

$SQLstment="SELECT userid,realname FROM www_users ";
        echo '<tr><td>Select Prepared By :</td><td><Select name="Bank_Code2">';
        $resultindex=DB_query($SQLstment, $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code'])){
                echo  '<option value="'.$row['userid'].'"  '.((trim($_POST['Bank_Code2'])==trim($row['userid']))?'selected="selected"':'').'>'.$row['realname'].'</option>';
             }else{
                echo '<option value="'.$row['userid'].'">'.$row['realname'].'</option>';
             }
        }
        echo '</select></td></tr><tr><td>Authorised By :</td><td><Select name="Bank_Code">';
        $resultindex=DB_query($SQLstment, $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code'])){
                echo  '<option value="'.$row['userid'].'"  '.((trim($_POST['Bank_Code'])==trim($row['userid']))?'selected="selected"':'').'>'.$row['realname'].'</option>';
             }else{
                echo '<option value="'.$row['userid'].'">'.$row['realname'].'</option>';
             }
        }
echo '</select></td></tr>';
    
echo '<tr><td>CREDIT TERMS</td>'
   . '<td><input type="text" name="terms" value="'.$_POST['terms'].'"  maxlength="10" /></td></tr>';
       
echo '<tr><td>No of Lab categories :</td><td><input type="number" min=1 class="number" id="numberoflines" maxlength="1" size="4" name="numberoflines"  value="'.$_POST['numberoflines'].'"/>'
   . '<button type="button" id="noofquotes" name="noofquotes"/>Add Rows</button></td></tr>';
echo '<tr><td colspan="4"><div id="samplehtml">';

  if(isset($_POST['numberoflines'])){
      $n=new Reload();
      $n->construct();
      
     if(isset($_SESSION['_QuoteRegistrtiondata'])){
        echo  $_SESSION['_QuoteRegistrtiondata'];
      }
 }

echo '</div></td></tr>';
if($_SESSION['loadedit']==true){
echo '<tr><td colspan="4"><input type="submit" id="refreshid" name="Calculate" value="Refresh"/>'
. '<input type="submit" id="preview" name="editquote" value="edit Quote" style="float:right"/>';
}else{
  echo '<tr><td colspan="4"><input type="submit" id="refreshid" name="Calculate" value="Refresh"/>'
. '<input type="submit" id="preview" name="preview" value="Create Quote" style="float:right"/>';
  
}
echo '</td></tr>';
echo '</table></div></div></div>';
echo '</form></div>';

}
include('includes/footer.inc');

Class Reload{
    
Function UserSelection($Id,$RID,$vital=0){
     
    if(!isset($_POST['userselection'][$Id][$RID])){
       $results = ($vital==1?'checked':'');
    }else{
       $results = (($_POST['TEST'][$Id][$RID]==$RID)?'checked':'');
    }
    
    
    return $results;
}
     
function Gettests($sampleID, $Id, $PricingType) {
    global $db;
    $ResultIndex = DB_query("SELECT [ParameterID],[Parameter],[Limits],[Method],[vital],SalesPrice "
                 . " FROM [LaboratoryStandards] where [sampletypeid]='".$sampleID."'", $db);
    while ($rowdate = DB_fetch_array($ResultIndex)) {
        $dataarray[] = $rowdate;
    }
       
    $results = '';
    if ($PricingType == 2) {
        $results .= '<div class="row row-cols-1 row-cols-md-3 gx-4">';
        foreach ($dataarray as $key => $rowdate) {
            $RID  = $rowdate['ParameterID'];
            $Name = html_entity_decode($rowdate['Parameter']);
            $results .= sprintf(
                '<div class="col themed-grid-col">'
                . '<div class="form-group">'
                . '<div class="col-6">'
                . '<input type="checkbox" name="TEST[%s][%s]" value="%s" %s>%s'
                . '</div>'
                . '<div class="col-6">'
                . '<input type="text" class="number quoteamount" name="AMOUNT[%s][%s]" value="%s" placeholder="price">'
                . '</div>'
                . '</div>'
                . '</div>',
                $Id, 
                $RID,
                $RID,
                (($_POST['TEST'][$Id][$RID] == $RID) ? 'checked' : ''),
                $Name ,
                $Id,
                $RID,
                (empty($_POST['AMOUNT'][$Id][$RID])? $rowdate['SalesPrice']:$_POST['AMOUNT'][$Id][$RID])
            );
        }
        $results .= '</div>';
    }
       
    if ($PricingType == 1) {
        $results .= '<div class="row row-cols-1 row-cols-md-3 gx-4">';
        if (is_array($dataarray)) {
            foreach ($dataarray as $key => $rowdate) {
                $RID  = $rowdate['ParameterID'];
                $Name = html_entity_decode($rowdate['Parameter']);
                $vital = $rowdate['vital'];
                $results .= sprintf(
                    '<div class="col themed-grid-col">'
                    . '<div class="form-group">'
                    . '<div class="col-6">'
                    . '<input type="hidden" name="userselection[%s][%s]" value="%s"/>'
                    . '<input type="checkbox" name="TEST[%s][%s]" value="%s" %s>%s'
                    . '</div>'
                    . '<div class="col-6">'
                    . '<input type="text" class="number quoteamount" name="AMOUNT[%s][%s]" value="%s" placeholder="price">'
                    . '</div>'
                    . '</div>'
                    . '</div>',
                    $Id, 
                    $RID, 
                    ($_POST['TEST'][$Id][$RID]),
                    $Id,
                    $RID,  
                    $RID,
                    $this->UserSelection($Id,$RID,$vital),
                    $Name,
                    $Id,
                    $RID,
                    (empty($_POST['AMOUNT'][$Id][$RID])? $rowdate['SalesPrice']:$_POST['AMOUNT'][$Id][$RID])
                );
            }
        }
        $results .= '</div>';
    }
    
    if ($PricingType == 0) {
        unset($_POST['TEST'][$Id]);
        $results = '<div class="row row-cols-1 row-cols-md-3 gx-4">'
                 . '<div class="col themed-grid-col">Not yet Specified</div></div>';
    }
    
    return $results;
}

function ShowTestType($ID) {
    $object = 'Sample Standards:<input type="hidden"  name="sampletype[' . $ID . ']"  id="SAM_' . $ID . '"  value="'.$_POST['sampletype'][$ID].'">'
           . '<input type="text" class="sampletype"  name="samplename[' . $ID . ']"  value="'.$_POST['samplename'][$ID].'" id="PAR_' . $ID . '" placeholder="Search sample standards" onMouseOver="showtooltip(event);" onkeyup="handleCustomerNameInput(event)"><div class="dropdown" id="dropdown_' . $ID . '"></td>';

    $arraypricing = array();
    $arraypricing[0] = '';
    $arraypricing[1] = 'Full Package Test';
    $arraypricing[2] = 'Selection';
          
    $object .= '<td>Parameters:<select name="Pricing['.$ID.']"  class="Pricing" id="PRC_'.$ID.'">';
    foreach ($arraypricing as $rowdata => $RowValue) {
        $object .= sprintf('<option value="%s" %s>%s</option>', $rowdata, (($_POST['Pricing'][$ID] == $rowdata) ? 'selected="selected"' : ""), $RowValue);
    }
    $object .= '</select></td>';

    return $object;
}

function construct() {
    $sampletable = '<table class="table-bordered">';
    for ($index = 1; $index <= $_POST['numberoflines']; $index++) {
        $Y = $index;
        $sampletable .= sprintf('<tr><td>%s</td></tr>', $this->ShowTestType($Y));
        $sampletable .= sprintf('<tr><td colspan="4"><div id="FLEX_%d">%s</div></td></tr>',
             $Y, $this->Gettests($_POST['sampletype'][$Y], $Y, $_POST['Pricing'][$Y]));
    }
    $sampletable .= '</table>';
    $_SESSION['_QuoteRegistrtiondata'] = $sampletable;
}


}

function getFooter($DOC){
       
 $filename= 'quotes/'.trim($DOC).'.terms';
if($filename){
 $paymentterms =  file_get_contents($filename);
 }

 $return = ((mb_strlen($paymentterms)>1)?$paymentterms:$_SESSION['paymentterms']); 
 
 return $return;
}
?>

<script>         
    $('#new_customer').click(function(){
       uni_modal("New Customer","quickCustomer.php",'mid-large')
    });
    
    $(".sampletype").click(function(){
        $.fn.quotesampletype($(this));
    });

    $(".Pricing").click(function(){
        $.fn.quotePricing($(this));
    }); 
    
    $("#Qdiscount").click(function(){
        $.fn.updatediscount();
    }); 
    
    async function handleCustomerNameInput(event) {
    // Get the value of the input field
    const customerName = event.target.value;
    const inputId = event.target.id;
    const rowid = inputId.split('_');
    const dropdownid='dropdown_'+rowid[1] ;
    const sampid='SAM_'+rowid[1] ;
         // You can now use customerName to perform further actions
    console.log(customerName);
         // Example: If you need to make an asynchronous call
    try {
        // Example: Fetch data from an API
        let response = await fetch('Ajax/searchsamples.php?query=' + encodeURIComponent(customerName));
            const parameterslist = await response.json();
        //`sampletypeid`,`sampletypeName`
        const dropdown = document.getElementById(dropdownid);
        dropdown.innerHTML = '';

        if (parameterslist.length > 0) {
            dropdown.style.display = 'block';

            parameterslist.forEach(parameter => {
                const div = document.createElement('div');
                div.textContent = parameter.sampletypeName;
                div.dataset.code = parameter.sampletypeid;
                div.dataset.name = parameter.sampletypeName;

                div.addEventListener('click', function() {
                    document.getElementById(sampid).value = this.dataset.code;
                    document.getElementById(inputId).value = this.dataset.name;
                    dropdown.style.display = 'none';
                       toastr.success(this.dataset.name);
                });

                dropdown.appendChild(div);
            });
        } else {
            dropdown.style.display = 'none';
        }
    } catch (error) {
        console.error('Error fetching parameters:', error);
    }
        
}
 

    document.getElementById('CustomerName').addEventListener('input', async function() {
    const query = this.value;

    if (query.length < 2) {
        document.getElementById('dropdown').style.display = 'none';
        return;
    }

    try {
        const response = await fetch('Ajax/searchcustomers.php?query=' + encodeURIComponent(query));
        const customers = await response.json();

        const dropdown = document.getElementById('dropdown');
        dropdown.innerHTML = '';

        if (customers.length > 0) {
            dropdown.style.display = 'block';

            customers.forEach(customer => {
                const div = document.createElement('div');
                div.textContent = customer.name;
                div.dataset.code = customer.code;
                div.dataset.name = customer.name;
                div.dataset.currency = customer.currency;
                div.dataset.salespersoncode= customer.salespersoncode;

                div.addEventListener('click', function() {
                     document.getElementById('CustomerID').value = this.dataset.code;
                     document.getElementById('CustomerName').value = this.dataset.name;
                     document.getElementById('currencycode').value = this.dataset.currency;
                     document.getElementById('salespersoncode').value = this.dataset.salespersoncode;
                     dropdown.style.display = 'none';
                });

                dropdown.appendChild(div);
            });
        } else {
            dropdown.style.display = 'none';
        }
    } catch (error) {
        console.error('Error fetching customers:', error);
    }
    });

    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('dropdown');
        if (!dropdown.contains(event.target) && event.target.id !== 'CustomerName') {
            dropdown.style.display = 'none';
        }
    });
  function showtooltip(event){
        const customerName = event.target.value;
        if(customerName!='') toastr.success(customerName);
   }		
</script>