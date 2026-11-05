<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Sales Quotation');
include('includes/CountriesArray.php');
$lastloop=array();
$www_users=array();

if(isset($_GET['No'])){
    
    $SQLstment="SELECT userid,realname,email,phone FROM www_users ";
    $Result = DB_query($SQLstment,$db);
    while($row = DB_fetch_array($Result)){
        $key = trim($row['userid']);
        $www_users[$key]=$row;
    }
    
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
      ,SalesHeader.[externaldocumentno] as BankCode
      ,SalesHeader.paymentterms
      ,SalesHeader.[discount]
      ,SalesHeader.vatinclusive
      from [SalesHeader] 
      join debtors on [SalesHeader].[customercode]=debtors.itemcode
      left join [salesrepsinfo] on [salesrepsinfo].code=[SalesHeader].[salespersoncode]
      where [SalesHeader].[documenttype]='54' 
      and [SalesHeader].[documentno]='".$_GET['No']."'";

    $Result = DB_query($SQL,$db);
    $myrow  = DB_fetch_row($Result);
    $Bankcode  = trim($myrow[17]);
    $Bankcode2 = trim($myrow[16]);
    $discount  = (int)($myrow[19]);
    $discount  = ($discount*0.01);
    $vatinclusive =(bool)($myrow[20]);
    $paymenttrerms = trim($myrow[18]);
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="Quotation";
    
    $pdf->addInfo('Title',_('Sales Order'));
    $pdf->addInfo('Subject',_('Sales Order'));
    $pdf->addInfo('Creator',_('SmartERP'));
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 10;
    $lastrow =0;
        
     $SQL=Sprintf("SELECT 
       SalesLine.[Itemlist] 
      ,SalesHeader.docdate
      ,SalesLine.[description] 
      ,salesline.Quantity
      ,[SalesLine].[unitofmeasure]
      ,SalesLine.UnitPrice as Sp 
      ,SalesHeader.[documentno]
      ,SalesHeader.[customercode]
      ,SalesLine.invoiceamount
      ,SalesHeader.[currencycode]
      ,SalesLine.vatamount
      ,(SalesLine.invoiceamount-SalesLine.vatamount) as netamt
      ,SalesHeader.[documenttype]
      ,salesline.vatrate
      ,SalesLine.code as itemcode
  FROM [SalesHeader] join SalesLine 
        on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype 
        and SalesHeader.documenttype=54  
        where SalesHeader.documentno='%s' order by code desc",$_GET['No']);
     
     $R1=0; $R2=0; $R3=0; $FontSize=9;
     $Total_Rows=0; $Row_Count=0;$interaction=0;
     
     $ResultIndex= DB_query($SQL,$db);
     $Total_Rows= DB_num_rows($ResultIndex);
     DB_free_result($ResultIndex);
    
     include('includes/PDFQuoteheader.inc');
     $YPos = $firstrowpos-6;
     $Results = DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
          $SalesLine[]= $rows;
     }
     
    
     foreach ($SalesLine as $key => $rows) {
         $interaction++; $No_of_rows=0;
         $R1 += ($rows['vatamount']);
         $R2 += ($rows['netamt']);
         $R3 += ($rows['invoiceamount']);
          
        $R4 = $discount * $R3;
        $Rv = $discount * $R1;
          
          
          $vatrate = (int) $rows['vatrate'];  $Ian = $YPos;
          $LeftOversa = $pdf->addTextWrap(10,$YPos,60,9,trim($rows['description']),'left');
          while(strlen($LeftOversa) > 0) { // If translated text is greater than 103, prints remainder
            $Ian -= $line_height;
            $LeftOversa = $pdf->addTextWrap(10,$Ian,60,9,$LeftOversa,'left');
            $No_of_rows++;
         }
            
         if($vatinclusive==TRUE){ 
              $pdf->addTextWrap(350, $YPos,90,9,number_format($rows['Sp']-$rows['vatamount'],2),'right');
         }else{  
              $pdf->addTextWrap(350, $YPos,90,9,number_format($rows['Sp'],2),'right');
         }
          
          $pdf->addTextWrap(440, $YPos,50,9,number_format($rows['vatamount'],2),'right');
          $pdf->addTextWrap(490, $YPos,90,9,number_format($rows['invoiceamount'],2),'right');
            
          $ITEMLIST = html_entity_decode($rows['Itemlist']);
          $ITEMLIST = strip_tags($ITEMLIST);  
          $stringarray = explode("\n",wordwrap($ITEMLIST,65,"<br>\n",TRUE));
          $No_of_rows += count($stringarray);
          printoverflow($No_of_rows,$stringarray,$YPos);
         
            if($No_of_rows>0){
                $YPos -= $line_height * $No_of_rows ;
            }else{
                $YPos -= $line_height  ;
            }
            
            if(($interaction==$Total_Rows)){
               printtotals();PrintBanksummary();
            }
     
         if(($YPos <= $PermanetPosition+40)){ 
              if(($interaction<$Total_Rows)){
                 printtotals(); PrintBanksummary();
                   include('includes/PDFQuoteheader.inc');
                   $YPos = $firstrowpos-6;
              }
          
         }
       
   }
  
 $pdf->Output($_SESSION['DatabaseName'] . '_QUOTATION_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf','I');
 $pdf->__destruct();
 
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
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
      where [SalesHeader].[documenttype]='54' 
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
      [SalesHeader].[docdate] desc
    limit  ". $_SESSION['DefaultDisplayRecordsMax'] ;
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Order <br />No</th><th>Edit</th>' 
             . '<th>Sales <br /> Order <br /> Document<br /> date</th>'
             . '<th>Sales <br /> Order <br />Due <br />Date</th>'
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
           
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td><td><a href="%s?EditNo=%s">Edit :%s</a></td>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno'],
                    'SalesQuotation.php',$row['documentno'],$row['documentno']);
   
        
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

function printoverflow($COUNT,$stringarray,$YPos){
    global $stringarray,$lastloop,$line_height,$pdf,$LeftOversa,$firstrowpos,$PermanetPosition;
           $Y = $YPos+10;
   
    for($x = 0; $x <= $COUNT; $x++) {
         $addtext = $stringarray[$x]; 
         $addtext = strip_tags($addtext); 
         $pdf->SetY($Y * -1);  
         $pdf->writeHTMLCell(0,$Y,90 ,'', $addtext);
         $Y -=  $line_height;
 
         if($Y <= $PermanetPosition+40){ 
           break 1;
         } 
   }
   
return $Y-10 ;
} 

function printtotals(){
    global $SavedYpos,$R1,$R3,$R4,$Rv,$R2,$pdf,$FontSize,$line_height,$vatinclusive;
  
        $zYpos = $SavedYpos;
        $pdf->addTextWrap(490,$zYpos,90,$FontSize, number_format($R2,2),'right');//Netamount
        $zYpos -=($line_height * 1.2);
        $pdf->addTextWrap(490,$zYpos,90,$FontSize, number_format($R4,2),'right');//Discount
        $zYpos -=($line_height * 1.2) ;
        $pdf->addTextWrap(490,$zYpos,90,$FontSize, number_format($R1-$Rv,2),'right');//Vat
        $zYpos -=($line_height * 1.2) ;
        $pdf->addTextWrap(490,$zYpos,90,$FontSize, number_format($R3-$R4,2),'right');//30
    
}

function PrintBanksummary(){
   global $stringarray,$lastloop,$YPos,$line_height,$FontSize,$Bankcode,$Bankcode2,
         $pdf,$LeftOversa,$PageNumber,$firstrowpos,$SummaryYpos,$paymenttrerms;
   global $db,$CurrencyName,$www_users;
  
    $Y=$SummaryYpos-($line_height*3.5);
    if(isset($_SESSION['RomalpaClause'])){
            $zYpos =$Y;
            $pdf->SetY($zYpos * -1);
            $LeftOvers = $pdf->writeHTMLCell(0,0,42,$pdf->GetY(),html_entity_decode($_SESSION['RomalpaClause']));
    } 
    
      
$Y -= ($line_height*15);
$pdf->addText(42,$Y,12,_('Payment Terms: ').$paymenttrerms);
     
$Y=($Y-($line_height*5)); 
$pdf->addText(42,$Y,12,_('Prepared By  ').$www_users[$Bankcode2]['realname']);
$pdf->addText(250,$Y,12,_('Authorized By ').$www_users[$Bankcode]['realname']);
$Y -= $line_height;
$pdf->SetTextColor(0,0,255);

$pdf->addText(42,$Y,12,_('Email :').$www_users[$Bankcode2]['email']);
$pdf->addText(250,$Y,12,_('Email :').$www_users[$Bankcode]['email']);
$pdf->SetTextColor(0,0,0);
$Y -= $line_height;
$pdf->addText(42,$Y,12,_('Mobile :').$www_users[$Bankcode2]['phone']);
$pdf->addText(250,$Y,12,_('Mobile :').$www_users[$Bankcode]['phone']);
 
}

function getFooter($DOC){
       
 $filename= 'quotes/'.trim($DOC).'.terms';
if($filename){
 $paymentterms =  file_get_contents($filename);
 }

 $filename= 'quotes/'.trim($DOC).'.bank';
if($filename){
 $bankaccountdetails = file_get_contents($filename);
  }
 $return = array('paymentterms'=>((mb_strlen($paymentterms)>1)?$paymentterms:$_SESSION['paymentterms']),
  'bankaccountdetails'=>((mb_strlen($bankaccountdetails)>1)?$bankaccountdetails:$_SESSION['bankaccountdetails'])); 
 
 return $return;
}
   