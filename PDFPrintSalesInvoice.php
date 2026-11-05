<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Sales Invoice');
    
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
      ,[SalesHeader].[lpo]
      ,[SalesHeader].[printed]
      ,[debtors].middlen
      ,SalesHeader.[discount]
      ,SalesHeader.vatinclusive
      ,SalesHeader.paymentterms
      from [SalesHeader] 
      join SalesLine on [SalesHeader].[documentno]=SalesLine.[documentno] 
      join debtors on [SalesHeader].[customercode]=debtors.itemcode
      where [SalesHeader].[documenttype]='10' and [SalesHeader].[documentno]='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    $Discount = $myrow[19];
    $VATinclusive =(bool) $myrow[20];
    
    $PaperSize='A4_Landscape';
    include('includes/PDFStarter.php');
    $headerName="INVOICE";
    
    $pdf->addInfo('Title',_('Sales Invoice'));
    $pdf->addInfo('Subject',_('Sales Invoice'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFInvoiceheader.inc');
     
     
     $SQL=Sprintf("SELECT 
      SalesLine.[description] 
      ,salesline.Quantity,
       SalesLine.UnitPrice as Sp ,
       SalesLine.unitofmeasure as UOMDesc
      ,SalesLine.invoiceamount
      ,SalesLine.vatamount
      ,SalesLine.invoiceamount-SalesLine.vatamount as netamt
      ,salesline.vatrate
      ,salesline.[sampleID]
  FROM [SalesLine] 
   where SalesLine.documenttype =10  
   and  SalesLine.documentno='%s' ",$_GET['No']);
     
     $FontSize = 12;
     $YPos=$firstrowpos-20;
     $R1=0;$R2=0;$R3=0;$R4=0; $R5=0;
      
     $DiscountAmount = ($Discount * 0.01);
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         $R1 +=$rows['vatamount'];
         $R2 +=$rows['netamt'];
         $R3 +=$rows['invoiceamount'];
          if($VATinclusive==False){
               $PRICE = $rows['Sp'];
          }else{
               $PRICE = $rows['Sp']-$rows['vatamount'];
          }
         $qty =(int)$rows['Quantity'];
         $R4 = $DiscountAmount * $R3;
         $Rv = $DiscountAmount * $R1;
         $Itemlist = strip_tags($rows['sampleID'],"<b><u><i><a><img><p><br><strong><em><font><tr><blockquote>"); //supprime tous les tags sauf ceux reconnus

          $LeftOversDescription = $pdf->addTextWrap(42, $YPos,300, $FontSize,$rows['description'].' Sample ID :'.$Itemlist,'left');
          $LeftOvers = $pdf->addTextWrap(495, $YPos, 85, $FontSize, number_format($PRICE,2),'right');
          $LeftOvers = $pdf->addTextWrap(590, $YPos, 55, $FontSize, number_format($rows['vatrate'],2),'right');
          $LeftOvers = $pdf->addTextWrap(650, $YPos, 70, $FontSize, number_format($rows['vatamount'],2),'right');
          if($VATinclusive==False){
            $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$YPos,90,$FontSize,number_format($rows['invoiceamount'],2),'right');
          }else{
            $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$YPos,90,$FontSize,number_format($rows['netamt'],2),'right');
          }
          
        while(strlen($LeftOversDescription) > 0) { // If translated text is greater than 103, prints remainder
            $YPos -= $line_height;
            $LeftOversDescription = $pdf->addTextWrap(42,$YPos,250, $FontSize,$LeftOversDescription,'left');
         }
            
         $YPos -= $line_height *1.2;
         if($YPos < $lastrow){
             $PageNumber++;
             include('includes/PDFInvoiceheader.inc');
              $YPos=$firstrowpos;
         }
       
     }
     
        $zYpos = $Bottom_Margin+105+100;
         if($VATinclusive==False){
        $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R2,2),'right');
         }else{
          $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R2-$R1,2),'right');
         }
        $zYpos -=$line_height *1.8;
        $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R4,2),'right');
        $zYpos -=$line_height *2.2;
        $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R1-$Rv,2),'right');
        $zYpos -=$line_height *2.6;
         if($VATinclusive==False){
           $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R3-$R4,2),'right');
         }else{
           $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R2-$R4,2),'right');
         }
         
       
          if(isset($_SESSION['RomalpaClause'])){
            $zYpos = $Bottom_Margin+215;
            $pdf->SetY($zYpos * -1);
            $Y = $pdf->GetY() ;
            $pdf->SetFont('helvetica','',8);
            $LeftOvers = $pdf->writeHTMLCell(0,0,42,$Y,html_entity_decode($_SESSION['RomalpaClause']));
          } 
           
            $YPos = $Bottom_Margin+50;
            $LeftOvers = $pdf->addTextWrap(42,$YPos,100, $FontSize,_('Authorised By :'),'left');
            $pdf->line(150,$YPos,500,$YPos);
            
            $LeftOvers = $pdf->addTextWrap(520,$YPos,100, $FontSize,_('Date:'),'right');
            $pdf->line(625,$YPos,700,$YPos);
            
            $YPos-= $line_height*3 ;
            $LeftOvers = $pdf->addTextWrap(42,$YPos,80, $FontSize,_('Sign:'),'right');
            $pdf->line(150,$YPos,250,$YPos);
            
   $pdf->Output($_SESSION['DatabaseName'] . '_INVOICE_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf','D');
   $pdf->__destruct();
    
    DB_query("Update [SalesHeader] set printed=1 where SalesHeader.documenttype=10 and SalesHeader.documentno='".$_GET['No']."'", $db);
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';



echo '<div class="card">'
    . '<table class="table-bordered"><tr>'
    . '<td><input type="submit" value="Refresh" id="f1lt3r" class="btn-info" />'
    . '<input type="hidden" name="CustomerID" id="CustomerID"/></td>'
    . '<td><input type="button" id="filtercustomer" value="Search Customer" class="btn-info" />Customer Name</td>'
    . '<td><input type="text" name="CustomerName" id="CustomerName" readonly="readonly"/></td></tr>'
    . '<tr><td colspan="2"><input type="submit" id="filterdocumentno" name="filterdocumentno" value="Search Invoice No" class="btn-info"/>Filter By Document No</td>'
    . '<td><input type="text" name="documentno" id="documentno"/></td></tr>'
    . '</table></div>';


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
      ,[SalesHeader].[userid] ,
      sum(SalesLine.[invoiceamount]) as OrderValue
      from [SalesHeader] join SalesLine on 
      [SalesHeader].[documentno]=SalesLine.[documentno] 
      where [SalesHeader].[documenttype]='10' ".    
      ((mb_strlen($_POST['CustomerID'])>0)?" and [SalesHeader].[customercode]='".$_POST['CustomerID']."'":'').
      ((mb_strlen($_POST['documentno'])>0)?" and [SalesHeader].[documentno] like '%".$_POST['documentno']."%'":'')."
       group by 
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
      order by 
      [SalesHeader].[docdate] desc limit ". $_SESSION['DefaultDisplayRecordsMax'] ."";
    $Result=DB_query($SQL,$db);
       
    
    Echo '<div class="card">'
             . '<Table class="table-bordered"><tr>'
             . '<th>Invoice <br />No</th>' 
             . '<th>Date</th>'
             . '<th>Customer <br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Sales <br />Order<br /> Value</th>'
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
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['salespersoncode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
          
         
        echo '</tr>';
  }
        
echo '</table></DIV>';
echo '</div></form>';

include('includes/footer.inc');

}   

 
 
?>