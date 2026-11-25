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
      from [salesheader] 
      join [salesline] on [salesheader].[documentno]=salesline.[documentno]
      join [debtors] on [salesheader].[customercode]=[debtors].[itemcode]
      where [salesheader].[documentno]='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
        
     $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="Stock Returns Note";
    
    $pdf->addInfo('Title',_('Sales Credit Note'));
    $pdf->addInfo('Subject',_('Sales Credit Note'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
    include('includes/PDFcreditnoteheader.inc');
     //[Quantity],[UnitPrice],[vatamount],[invoiceamount]
                $SQL=Sprintf("SELECT stockmaster.barcode ,
                    salesheader.docdate ,
                    salesline.[description] ,
                    salesline.[Quantity], 
                   [salesline].UOM, 
                   (case [salesline].UOM when 'fulqty'  then (select salesline.UnitPrice * stockmaster.partperunit from stockmaster where stockmaster.itemcode=salesline.code) else (salesline.UnitPrice) end) as Sp , 
                   (case [salesline].UOM when 'fulqty' then (select rtrim([unit].[descrip])  from [unit] join stockmaster on stockmaster.units=[unit].code and stockmaster.itemcode=salesline.code) 
                   else (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.subunits=[unit].code 
                   and stockmaster.itemcode=salesline.code) end) as UOMDesc , 
                   salesheader.[documentno] , 
                   salesheader.[customercode] , 
                   salesheader.[documenttype] 
                   FROM [salesheader] join salesline on salesheader.documentno=salesline.documentno and salesheader.documenttype=salesline.documenttype 
                   and salesheader.documenttype='11' left join stockmaster on stockmaster.itemcode=salesline.code 
                   where salesheader.documentno='%s' ",$_GET['No']);
     
     
     $FontSize = 12;
     $YPos = $firstrowpos-20;
      
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,100, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(145, $YPos,100, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(362, $YPos, 85, $FontSize, $rows['UOMDesc'],'left');
          $LeftOvers = $pdf->addTextWrap(505, $YPos, 20, $FontSize, number_format($rows['Quantity'],0),'right');
               
         $YPos -= $line_height * 2;
         if($YPos < $Bottom_Margin+100){
             $PageNumber++;
             
             include('includes/PDFcreditnoteheader.inc');
              $YPos = $firstrowpos;
         }
                
     }
     
     $YPos=$Bottom_Margin+200;
     
     $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,_('Returned by (Name) :'),'left');
     $YPos-= $line_height;
     $LeftOvers = $pdf->line($Page_Width-$Right_Margin,$YPos,$Left_Margin,$YPos);
     $LeftOvers = $pdf->addTextWrap(42,$YPos -= $line_height * 3,250, $FontSize,_('ID No:'),'left');
     
     $YPos=$Bottom_Margin+200;
     $LeftOvers = $pdf->addTextWrap(145,$YPos,250, $FontSize,_('Date:'),'right');
     $LeftOvers = $pdf->addTextWrap(145,$YPos -= $line_height * 4,250, $FontSize,_('Sign:'),'right');
          
            
    $pdf->OutputD($_SESSION['DatabaseName'] . '_Returns_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
    
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


$SQL="Select 
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
      from [salesheader] join salesline on 
      [salesheader].documentno=salesline.[documentno]  
      where [salesheader].[documenttype]='11' 
      order by [salesheader].[docdate] desc";
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
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
              
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