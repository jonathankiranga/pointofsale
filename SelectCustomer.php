<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Customer List');
include('includes/header.inc');
$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

if(isset($_GET['itemcode'])){
    $_SESSION['Customeritemcode']=$_GET['itemcode'];
}

if(isset($_GET['newsearch'])){
    unset($_SESSION['Customeritemcode']);
}

if(isset($_SESSION['Customeritemcode'])){
    
        $ErrMsg = _('The customer name requested cannot be retrieved because');
	$result = DB_query("Select "
               . 'itemcode,'
               . 'customer,'
               . 'creditlimit,'
               . 'phone,'
               . 'email,'
               . 'salesman,'
               . 'inactive,'
               . 'customerposting '
               . "from debtors where itemcode='".$_SESSION['Customeritemcode']."'", $db, $ErrMsg);
	if ($myrow = DB_fetch_array($result)) {
		$CustomerName = htmlspecialchars($myrow['customer'], ENT_QUOTES, 'UTF-8', false);
		$PhoneNo = $myrow['phone'];
	} 
    
    echo '<p class="page_title_text"><img src="css/' . $Theme . '/images/customer.png" title="' . _('Customer') . '" alt="" />' . ' ' . _('You have selected Customer') . ' : ' . $_SESSION['Customeritemcode'] . ' - ' . $CustomerName . ' - ' . $PhoneNo . '</p>';
    echo '<div class="page_help_text">' . _('Select a menu option to operate using this customer') . '.</div><br />';

	echo '<table cellpadding="4" width="90%" class="table table-bordered">
                <tr>
                        <th style="width:33%">' . _('Customer Inquiries') . '</th>
                        <th style="width:33%">' . _('Customer Transactions') . '</th>
                        <th style="width:33%">' . _('Customer Maintenance') . '</th>
                </tr>';
	echo '<tr><td valign="top" class="select">';
	/* Customer Inquiry Options */
	echo '<a href="PrintCustStatements.php?FromCust=' . $_SESSION['Customeritemcode'] . '&amp;ToCust=' . $_SESSION['Customeritemcode'] . '&amp;PrintPDF=Yes">' . _('Print Customer Statement') . '</a><br />';
	
	echo '</td><td valign="top" class="select">';
	echo '<a href="receipts.php?SelectedCustomer=' . $_SESSION['Customeritemcode'] . '">' . _('Record Receipts') . '</a><br />';
	echo '<a href="ReceitsAllocation.php?CustomerID=' . $_SESSION['Customeritemcode'] . '">' . _('Allocate Receipts OR Credit Notes') . '</a><br />';
	
	echo '</td><td valign="top" class="select">';
	echo '<a href="Customer.php">' . _('Add a New Customer') . '</a><br />';
	echo '<a href="Customer.php?Modify=' . $_SESSION['Customeritemcode'] . '">' . _('Modify Customer Details') . '</a><br />';
	
	echo '</td>';
	echo '</tr></table><br />';

    
}

echo '<form><div class="card">
    <table class="table table-bordered">
    <thead>'
        . '<TR>'
        . '<th class="ascending">Account Code</th>'
        . '<th class="ascending">Customer Name</th>'
        . '<th>Discount Rate</th>'
        . '<th>Average Sales</th>'
        . '<th>Telephone No</th>'
        . '<th>Email</th>'
        . '<th>Sales Rep Code</th>'
        . '<th>Posting Group</th>'
        . '</tr></thead>';
      #DATE_ADD("2017-06-15", INTERVAL 10 DAY); 
       $results=DB_query('Select 
                        itemcode,
                        customer,
                        creditlimit,
                        AVG([CustomerStatement].[Grossamount]) as Totsales,
                        phone,
                        email,
                        salesman,
                        customerposting 
                        from debtors left join [CustomerStatement] on itemcode=[Accountno]
                        and Documenttype=10 and ([CustomerStatement].[Date]
                        between DATE_ADD(sysdate(),INTERVAL -12 MONTH) and sysdate() )
                        group by 
                        itemcode,
                        customer,
                        creditlimit,
                        phone,
                        email,
                        salesman,
                        customerposting order by customer asc', $db);
       $k=0;
       while($rows=DB_fetch_array($results)){
           echo '<tr>';
           echo sprintf('<td><a href="%s?itemcode=%s">%s</a></td>',$thispage, trim($rows['itemcode']),trim($rows['itemcode']));
           echo '<td>'.$rows['customer'].'</td>';
           echo '<td>'.number_format($rows['creditlimit'],0).'</td>';
           echo '<td>'.number_format($rows['Totsales'],0).'</td>';
           echo '<td>'.trim($rows['phone']).'</td>';
           echo '<td>'.trim($rows['email']).'</td>';
           echo '<td>'.$rows['salesman'].'</td>';
           echo '<td>'.$rows['customerposting'].'</td>'
          . '</tr>';
            
       }        

echo '</tbody></table></div></form>';


include('includes/footer.inc');


?>
