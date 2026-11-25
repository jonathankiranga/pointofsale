<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Picking List');

if(isset($_GET['No'])){
    
$SQL="select 
       [salesheader].[documentno]
      ,[salesheader].[docdate]
      ,[salesheader].[oderdate]
      ,[salesheader].[duedate]
      ,[salesheader].[customercode]
      ,[salesheader].[customername]
      ,[salesheader].[currencycode]
      ,[salesheader].[salespersoncode]
      ,[salesheader].[status]
      ,[salesheader].[userid]
      ,[debtors].email
      ,[debtors].city
      ,[debtors].postcode
      ,[debtors].country
      ,[debtors].phone
      ,[debtors].contact
      ,[salesheader].[externaldocumentno]
      from [salesheader]
      join [salesline] on [salesheader].[documentno]=salesline.[documentno] 
      join [debtors] on [salesheader].[customercode]=[debtors].[itemcode]
      where [salesheader].[documentno]='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
        
     $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="Laboratory Reagents ";
    
    $pdf->addInfo('Title',_('Sales Order'));
    $pdf->addInfo('Subject',_('Sales Order'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFreagentsheader.inc');
     
     
       $SQL=Sprintf("SELECT 
       stockmaster.barcode
      ,salesheader.docdate
      ,salesline.[description] 
      ,salesline.Quantity,
       [salesline].UOM,
       salesline.UnitPrice as Sp ,
	  (case [salesline].UOM when 'fulqty' 
          then (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.units=[unit].code  and stockmaster.itemcode=salesline.code) 
	  else (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.subunits=[unit].code  and stockmaster.itemcode=salesline.code)
	  end) as UOMDesc
      ,salesheader.[documentno]
      ,salesheader.[customercode]
      ,salesline.invoiceamount
      ,salesheader.[currencycode]
      ,salesheader.[documenttype]
     
  FROM [salesheader] join salesline 
        on salesheader.documentno=salesline.documentno 
        and salesheader.documenttype=salesline.documenttype 
        and salesheader.documenttype=19
        join stockmaster on stockmaster.itemcode=salesline.code
         where salesheader.documentno='%s' ",$_GET['No']);
     
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
             
             include('includes/PDFreagentsheader.inc');
              $YPos = $firstrowpos;
         }
                
     }
     
     $YPos = $Bottom_Margin+250;
     $LeftOvers = $pdf->addTextWrap(40,$YPos,100, $FontSize,_('Name of Laboratory officer :'),'left');
     $LeftOvers = $pdf->line($Page_Width-$Right_Margin,$YPos- $line_height,$Left_Margin,$YPos- $line_height);
     
     $YPos = $Bottom_Margin+210;
     $LeftOvers = $pdf->addTextWrap(40,$YPos - $line_height ,100, $FontSize,_('Designation:'),'left');
     $LeftOvers = $pdf->line($Page_Width-$Right_Margin,$YPos- $line_height,$Left_Margin,$YPos- $line_height);
    
     $YPos = $Bottom_Margin+180;
     $LeftOvers = $pdf->addTextWrap(40,$YPos - $line_height,100, $FontSize,_('Sign:'),'left');
     $LeftOvers = $pdf->addTextWrap(245,$YPos - $line_height,100, $FontSize,_('Supervisor:'),'left');
     $LeftOvers = $pdf->line($Page_Width-$Right_Margin,$YPos- $line_height,$Left_Margin,$YPos- $line_height);
        
            
    $pdf->OutputD($_SESSION['DatabaseName'] . '_LabReagents_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


$SQL="Select 
       [salesheader].[externaldocumentno]
      ,[salesheader].[documentno]
      ,[salesheader].[docdate]
      ,[salesheader].[oderdate]
      ,[salesheader].[duedate]
      ,[salesheader].[customercode]
      ,[salesheader].[customername]
      ,[salesheader].[currencycode]
      ,[salesheader].[salespersoncode]
      ,[salesheader].[status]
      ,[salesheader].[userid] 
      from  [salesheader] 
      where [salesheader].[documenttype]='19' 
      order by [salesheader].[documentno] desc limit  100";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Registration No</th>'
             . '<th>Labtest Order<br />No</th>' 
             . '<th>Document<br /> date</th>'
             . '<th>Order <br />Due <br />Date</th>'
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
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['externaldocumentno']);
              
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