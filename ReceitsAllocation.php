<?php
include('includes/session.inc');
$Title = _('Customer Receipts Allocation');
include('includes/header.inc');  
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.

    $FR = new FinancialPeriods();
    
    if(isset($_POST['cancel'])){
        unset($_POST);
    }
    
    if(isset($_GET['CustomerID'])){
        $_POST['CustomerID']=$_GET['CustomerID'];
    }
    
    
    if(isset($_POST['Auto'])){
     ##   DB_query("EXEC	[autoallocatedebtors]  @accountno ='".$_POST['CustomerID']."'", $db);
        autoallocatedebtors($_POST['CustomerID']);
    }
 
    echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/sales.png" title="' . _('Customer Receipts Allocation') .'" alt="" />' . ' ' . _('Customer Receipts Allocation') . '</p>';
    echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"><div>';
    echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
    if(isset($_POST['CustomerID'])){
      echo  '<input  type="hidden" name="CustomerID" value="'.$_POST['CustomerID'].'"/>';
      echo  '<input  type="hidden" name="CustomerName" value="'.$_POST['CustomerName'].'"/>';
      
      echo '<p class="page_title_text">For Account :'.$_POST['CustomerName'].'</p>';
      
        echo '<table><tr><th>DATE</th><th>DOC No</th><th>Doc Type</th><th class="number">Amount</th>'
             .'<th class="number">Unallocated</th><th class="number">To Allocate</th></tr>';
        
      $sql="SELECT [Date]
      ,[Documentno]
      ,(select systypes_1.typename from systypes_1 where systypes_1.typeid=CustomerStatement.Documenttype)  as doctypes
      ,[Accountno]
      ,[Grossamount]
      ,[JournalNo]
      ,([Grossamount]+ IFNULL((SELECT sum([amount]) FROM [ReceiptsAllocation] 
         where [itemcode]=[CustomerStatement].[Accountno] 
          and [date]=[CustomerStatement].[date] 
          and [invoiceno]=[CustomerStatement].[Documentno] 
          and [journalno]=[CustomerStatement].[JournalNo]),0)) as Pamount
      FROM [CustomerStatement] where [CustomerStatement].[Accountno]='".$_POST['CustomerID']."'";

      $ResultIndex=DB_query($sql,$db);
      while($row=DB_fetch_array($ResultIndex)){
          $maxamount = $row['Pamount'];
          if($maxamount>0){
                echo sprintf('<tr>'
                           . '<td>%s</td>'
                           . '<td>%s</td>'
                           . '<td>%s</td>'
                           . '<td><input type="text" class="number" value="%f" readonly="readonly"/></td>'
                           . '<td><input type="text" class="number" value="%f" name="Minus['.$row['JournalNo'].']" readonly="readonly"/></td>'
                           . '<td></td>'
                           . '</tr>',ConvertSQLDate($row['Date']),
                           $row['Documentno'],
                           $row['doctypes'],
                           $row['Grossamount'],
                           $maxamount);
            }
      }

      echo '<tr><td colspan="4"><input type="submit" name="Auto" value="Auto Allocate"/></td><td><input type="submit" name="cancel" value="Select Another account"/></td></tr>';
      echo '</table>';
      
    } else {

    echo '<table>';
    echo '<tr><td>Customer ID</td>'
        . '<td><input type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchcustomer" value="Search Customer"/>'
        . '<input type="hidden" name="salespersoncode" id="salespersoncode" value=""/>'
        . '<input type="hidden" name="currencycode" id="currencycode" value=""/></td></tr>'
        . '<tr><td>Customer Name</td>'
        . '<td><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" /></td></tr>';
   
    echo '<tr><td></td><td><input type="submit" name="Submit" value="Select Account"/></td></tr>'
       . '</table>';
  
    }
    
   echo '</div></form>';

   include('includes/footer.inc');
   

?>