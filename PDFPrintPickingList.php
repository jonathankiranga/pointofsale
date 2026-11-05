<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Picking List');

if(isset($_GET['No'])){
    
$SQL="select 
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[oderdate]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid]
      ,[debtors].email
      ,[debtors].city
      ,[debtors].postcode
      ,[debtors].country
      ,[debtors].phone
      ,[debtors].contact
      ,stockledger.jobcard
      from [stockledger] 
      join [SalesLine] on [stockledger].[invref]=[SalesLine].[documentno]
      join [SalesHeader] on [SalesHeader].[documentno]=SalesLine.[documentno] 
      join [debtors] on [SalesHeader].[customercode]=[debtors].[itemcode]
      where [stockledger].[jobcard]='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
        
     $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="Delivery Note";
    
    $pdf->addInfo('Title',_('Sales Order'));
    $pdf->addInfo('Subject',_('Sales Order'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFPicklistheader.inc');
     
     
     $SQL=Sprintf("SELECT 
         stockmaster.barcode ,
         SalesHeader.docdate ,
         SalesLine.[description] ,
        (case [SalesLine].UOM when 'fulqty' then (select stockledger.fulqty)  else (select stockledger.loosqty)  end) as Quantity,
        [SalesLine].UOM,
        (case [SalesLine].UOM when 'fulqty'  then (select SalesLine.UnitPrice*stockmaster.partperunit from stockmaster where stockmaster.itemcode=SalesLine.code)  else (SalesLine.UnitPrice) end) as Sp , 
        (case [SalesLine].UOM when 'fulqty' then (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.units=[unit].code    and stockmaster.itemcode=SalesLine.code) else (select rtrim([unit].[descrip])  from [unit] join stockmaster on stockmaster.subunits=[unit].code and stockmaster.itemcode=SalesLine.code) end) as UOMDesc ,
        stockledger.jobcard as [documentno] ,
        SalesHeader.[customercode] ,
        SalesHeader.[documenttype] 
        FROM [SalesHeader] join SalesLine 
        on SalesHeader.documentno=SalesLine.documentno and SalesHeader.documenttype=SalesLine.documenttype and SalesHeader.documenttype=1 
        join stockmaster on stockmaster.itemcode=SalesLine.code join stockledger on stockledger.invref=SalesLine.documentno 
        and stockledger.itemcode=SalesLine.code
        where stockledger.jobcard='%s' ",$_GET['No']);
     
     $FontSize = 12;
     $YPos = $firstrowpos-20;
      
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,100, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(362, $YPos, 85, $FontSize, $rows['UOMDesc'],'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, number_format(abs($rows['Quantity']),0),'right');
               
         $YPos -= $line_height * 2;
         if($YPos < $Bottom_Margin+100){
             $PageNumber++;
             
             include('includes/PDFPicklistheader.inc');
              $YPos = $firstrowpos;
         }
                
     }
     
     $YPos = $Bottom_Margin+250;
     $LeftOvers = $pdf->addTextWrap(40,$YPos,100, $FontSize,_('Name of Driver :'),'left');
     $LeftOvers = $pdf->addTextWrap(300,$YPos,50, $FontSize,_('ID No:'),'left');
     $LeftOvers = $pdf->addTextWrap(445,$YPos,50, $FontSize,_('Mobile:'),'left');
     $LeftOvers = $pdf->line($Page_Width-$Right_Margin,$YPos- $line_height,$Left_Margin,$YPos- $line_height);
     
     $YPos = $Bottom_Margin+210;
     $LeftOvers = $pdf->addTextWrap(40,$YPos - $line_height ,100, $FontSize,_('Vehicle:'),'left');
     $LeftOvers = $pdf->addTextWrap(245,$YPos -= $line_height,100, $FontSize,_('Sign:'),'left');
     $LeftOvers = $pdf->line($Page_Width-$Right_Margin,$YPos- $line_height,$Left_Margin,$YPos- $line_height);
    
     $YPos = $Bottom_Margin+180;
     $LeftOvers = $pdf->addTextWrap(40,$YPos - $line_height,100, $FontSize,_('Received By:'),'left');
     $LeftOvers = $pdf->addTextWrap(245,$YPos - $line_height,100, $FontSize,_('Sign:'),'left');
          
            
    $pdf->OutputD($_SESSION['DatabaseName'] . '_Pickinglist_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


$SQL="Select 
        stockledger.jobcard
      ,[SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[oderdate]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid] 
      from stockledger join SalesLine on 
      stockledger.invref=SalesLine.[documentno] 
      join [SalesHeader] on [SalesHeader].documentno=SalesLine.[documentno] 
      where [SalesHeader].[documenttype]='1' 
      order by [SalesHeader].[documentno] desc limit 50";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Pick List <br />No</th>'
              . '<th>Sales Order<br />No</th>' 
             . '<th>Sales <br /> Order <br /> Document<br /> date</th>'
             . '<th>Sales <br /> Order <br />Due <br />Date</th>'
             . '<th>Customer <br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Currency</th>'
             . '<th>Sales<br /> Person</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
          
            . '</tr>';
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
           
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['jobcard'],$row['documentno']);
              
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['salespersoncode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
          
         
        echo '</tr>';
  }
        
    echo '</table><br />';
	
echo '</div></form>';

include('includes/footer.inc');

}   


?>