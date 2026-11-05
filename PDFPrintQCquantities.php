<?php 
$PageSecurity=0;

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Picking List');

if(isset($_GET['No'])){
    
$SQL="select 
       [PurchaseHeader].[documentno]
      ,[PurchaseHeader].[docdate]
      ,[PurchaseHeader].[oderdate]
      ,[PurchaseHeader].[duedate]
      ,[PurchaseHeader].[vendorcode]
      ,[PurchaseHeader].[vendorname]
      ,[PurchaseHeader].[currencycode]
      ,[PurchaseHeader].[status]
      ,[PurchaseHeader].[userid]
      ,[productionEmployee].email
      ,[productionEmployee].city
      ,[productionEmployee].postcode
      ,[productionEmployee].country
      ,[productionEmployee].phone
      ,stockledger.jobcard
      from [PurchaseHeader] 
      join [PurchaseLine] on [PurchaseHeader].[documentno]=[PurchaseLine].[documentno]
      join [stockledger] on [PurchaseHeader].[documentno]=stockledger.jobcard
      join [productionEmployee] on [PurchaseHeader].[vendorcode]=[productionEmployee].[code]
      where [PurchaseHeader].[documentno]='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="Quality Control";
    
    $pdf->addInfo('Title',_('Quality Control'));
    $pdf->addInfo('Subject',_('Quality Control'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFQCtheader.inc');
     
     
     $SQL=Sprintf("SELECT 
         stockmaster.[barcode] ,
         PurchaseHeader.[docdate] ,
         PurchaseLine.[description] ,
        (case [PurchaseLine].UOM when 'fulqty' then (select stockledger.fulqty) else (select stockledger.loosqty) end) as Quantity,
        [PurchaseLine].[UOM],
        (case [PurchaseLine].UOM when 'fulqty' then (select (PurchaseLine.UnitPrice * stockmaster.partperunit) from stockmaster where stockmaster.itemcode=PurchaseLine.code)  else (PurchaseLine.UnitPrice) end) as Sp , 
        (case [PurchaseLine].UOM when 'fulqty' then (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.units=[unit].code and stockmaster.itemcode=PurchaseLine.code) else (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.subunits=[unit].code and stockmaster.itemcode=PurchaseLine.code) end) as UOMDesc ,
        stockledger.jobcard as [documentno] ,
        PurchaseHeader.[vendorcode] ,
        stockledger.doctyp as [documenttype]
        FROM [PurchaseHeader] 
        join PurchaseLine on PurchaseHeader.documentno=PurchaseLine.documentno and PurchaseHeader.documenttype=PurchaseLine.documenttype 
	join stockmaster on stockmaster.itemcode=PurchaseLine.code 
	join stockledger on stockledger.jobcard=PurchaseHeader.documentno and stockledger.doctyp=30
        where PurchaseHeader.documentno='%s' ",$_GET['No']);
     
     $FontSize = 12;
     $YPos=$firstrowpos-20;
      
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,100, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(362, $YPos, 85, $FontSize, $rows['UOMDesc'],'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize,  number_format( $rows['Quantity'],0),'right');
               
         $YPos -= $line_height * 2;
         if($YPos<$Bottom_Margin+100){
             $PageNumber++;
             include('includes/PDFQCtheader.inc');
              $YPos=$firstrowpos;
         }
                
     }
     
     $YPos=$Bottom_Margin+70;
     
     $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,_('Stores Received by (Name) :'),'left');
     $YPos-= $line_height;
     $LeftOvers = $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
     $LeftOvers = $pdf->addTextWrap(42,$YPos -= $line_height * 3,250, $FontSize,_('Designation:'),'left');
     
     $YPos=$Bottom_Margin+70;
     $LeftOvers = $pdf->addTextWrap(145,$YPos,250, $FontSize,_('Date:'),'right');
     $LeftOvers = $pdf->addTextWrap(145,$YPos -= $line_height * 4,250, $FontSize,_('Sign:'),'right');
          
            
    $pdf->OutputD($_SESSION['DatabaseName'] . '_QC_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


$SQL="select 
       [PurchaseHeader].[documentno]
      ,[PurchaseHeader].[yourreference]
      ,[PurchaseHeader].[docdate]
      ,[PurchaseHeader].[vendorcode]
      ,[PurchaseHeader].[vendorname]
      ,SUM(PurchaseLine.[invoiceamount]) as OrderValue
      ,[PurchaseHeader].[currencycode]
      ,[PurchaseHeader].[status]
      ,[PurchaseHeader].[userid]
      from [PurchaseHeader] 
      join PurchaseLine on PurchaseLine.[documentno]=[PurchaseHeader].[documentno]
      where PurchaseHeader.documenttype='30' 
      Group by [PurchaseHeader].[documentno]
      ,[PurchaseHeader].[yourreference]
      ,[PurchaseHeader].[docdate]
      ,[PurchaseHeader].[oderdate]
      ,[PurchaseHeader].[duedate]
      ,[PurchaseHeader].[vendorcode]
      ,[PurchaseHeader].[vendorname]
      ,[PurchaseHeader].[currencycode]
      ,[PurchaseHeader].[status]
      ,[PurchaseHeader].[userid] 
      order by [PurchaseHeader].[docdate] desc";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>QC <br />Note</th>'
             . '<th>Work<br />No</th>' 
             . '<th>Work<br /> Order <br /> Document<br /> date</th>'
             . '<th>Employee<br />ID</th>'
             . '<th>Employee<br /> Name</th>'
             . '<th>AMOUNT</th>'
             . '<th>Currency</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
          
            . '</tr>';
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',$row['yourreference']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
                  
        echo '</tr>';
  }
        
    echo '</table><br />';
	
echo '</div></form>';

include('includes/footer.inc');

}   


?>