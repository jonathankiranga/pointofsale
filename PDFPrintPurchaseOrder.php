<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Purchase Order');

if(isset($_GET['No'])){
    
$SQL="Select
       [PurchaseHeader].[documentno]
      ,[PurchaseHeader].[docdate]
      ,[PurchaseHeader].[oderdate]
      ,[PurchaseHeader].[duedate]
      ,[PurchaseHeader].[vendorcode]
      ,[PurchaseHeader].[vendorname]
      ,[PurchaseHeader].[currencycode]
      ,[PurchaseHeader].[status]
      ,[PurchaseHeader].[userid]
      ,[creditors].email
      ,[creditors].city
      ,[creditors].postcode
      ,[creditors].country
      ,[creditors].phone
      ,[creditors].contact
      ,[PurchaseHeader].[yourreference]
      ,[PurchaseHeader].[printed]
      ,[creditors].vatregno
      from [PurchaseHeader] 
      join PurchaseLine on 
      [PurchaseHeader].[documentno]=PurchaseLine.[documentno] 
      join creditors on [PurchaseHeader].[vendorcode]=creditors.itemcode
      where ([PurchaseHeader].[documenttype]='18'  
      or [PurchaseHeader].[documenttype]='41'  or [PurchaseHeader].[documenttype]='20')
      and [PurchaseHeader].[documentno]='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    
    
    $PaperSize='A4_Landscape';
    include('includes/PDFStarter.php');
    $headerName="Purchase Order";
    
    $pdf->addInfo('Title',_('Purchase Invoice'));
    $pdf->addInfo('Subject',_('Purchase Invoice'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFpurchaseheader.inc');
     
     
     $SQL=Sprintf("SELECT 
       stockmaster.barcode
      ,Container.[descrip] as containername
      ,PurchaseHeader.docdate
      ,PurchaseLine.[description] 
      ,PurchaseLine.Quantity
      ,[PurchaseLine].UOM
       ,PurchaseLine.UnitPrice as Sp 
    ,(case [PurchaseLine].UOM when 'fulqty' 
    then (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.units=[unit].code and stockmaster.itemcode=PurchaseLine.code) 
    else (select rtrim([unit].[descrip]) from [unit] join stockmaster on stockmaster.subunits=[unit].code and stockmaster.itemcode=PurchaseLine.code)
    end) as UOMDesc
      ,PurchaseHeader.[documentno]
      ,PurchaseHeader.[vendorcode]
      ,PurchaseLine.invoiceamount
      ,PurchaseHeader.[currencycode]
      ,PurchaseLine.vatamount
      ,(PurchaseLine.invoiceamount - PurchaseLine.vatamount) as netamt
      ,PurchaseHeader.[documenttype]
      ,PurchaseLine.vatrate
  FROM [PurchaseHeader] join PurchaseLine on PurchaseHeader.documentno=PurchaseLine.documentno 
        and PurchaseHeader.documenttype=PurchaseLine.documenttype 
        join stockmaster on stockmaster.itemcode=PurchaseLine.code
        left join stockmaster Container on Container.itemcode=PurchaseLine.containercode
        where PurchaseHeader.documentno='%s' ",$_GET['No']);
     
     $FontSize = 12;
     $YPos=$firstrowpos-20;
     $R1=0;
     $R2=0;
     $R3=0;
     
     
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         $R1 +=$rows['vatamount'];
         $R2 +=$rows['netamt'];
         $R3 +=$rows['invoiceamount'];
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,80, $FontSize, $rows['barcode'],'left');
          $LeftOversDescription = $pdf->addTextWrap(125, $YPos,270, $FontSize, trim($rows['description']),'left');
          $LeftOvers = $pdf->addTextWrap(375, $YPos, 85, $FontSize, $rows['UOMDesc'],'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, $rows['Quantity'],'right');
          $LeftOvers = $pdf->addTextWrap(492, $YPos, 85, $FontSize, number_format($rows['Sp'],2),'right');
          $LeftOvers = $pdf->addTextWrap(590, $YPos, 55, $FontSize, number_format($rows['vatrate'],0),'right');
          $LeftOvers = $pdf->addTextWrap(650, $YPos, 70, $FontSize, number_format($rows['vatamount'],2),'right');
          $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$YPos,90,$FontSize,number_format($rows['netamt'],2),'right');
         while(mb_strlen($LeftOversDescription) > 5) { // If translated text is greater than 103, prints remainder
            $YPos -= $line_height;
            $LeftOversDescription = $pdf->addTextWrap(125,$YPos,270, $FontSize,trim($LeftOversDescription),'left');
         }
         
         $YPos -= $line_height * 2;
         if($YPos < $lastrow){
             $PageNumber++;
             include('includes/PDFpurchaseheader.inc');
              $YPos=$firstrowpos-20;
         }
               
        }
     
           
    $zYpos = $Bottom_Margin+80;
    $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R2,2),'right');
    
    $zYpos -= $line_height *2.5;
    $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R1,2),'right');
    
    $zYpos -= $line_height *2.5 ;
    $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R3,2),'right');

     $YPos= $Bottom_Margin+70;
     $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,_('Ordered By :').$_SESSION['UsersRealName'],'left');
     $LeftOvers = $pdf->addTextWrap(145,$YPos,250, $FontSize,_('Sign:'),'right');
     $pdf->line(400,$YPos,500,$YPos);
     
     
     $YPos-= $line_height * 2.5;
     $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,_('Reviewed By :'),'left');
     $pdf->line(120,$YPos,300,$YPos);
     $LeftOvers = $pdf->addTextWrap(145,$YPos,250, $FontSize,_('Sign:'),'right');
     $pdf->line(400,$YPos,500,$YPos);
     
     $YPos-=  $line_height * 2.5;
    
     $LeftOvers = $pdf->addTextWrap(42,$YPos,250, $FontSize,_('Authorised By :'),'left');
     $LeftOvers = $pdf->addTextWrap(145,$YPos,250, $FontSize,_('Sign:'),'right');
    $pdf->line(120,$YPos,300,$YPos);
    $pdf->line(400,$YPos,500,$YPos);
    $pdf->OutputD($_SESSION['DatabaseName'] . '_Purchase_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
    DB_query("Update [PurchaseHeader] set printed=1 where PurchaseHeader.documenttype=18 and PurchaseHeader.documentno='".$_GET['No']."'", $db);
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
   . '<img src="css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"  id="salesform">';
echo '<div><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] .'"/>';

echo '<div class="card"><table class="table table-bordered"><tr>'
    . '<td><input type="button" id="searchvendor" value="Search for Vendor"/>'
    . '<input type="hidden" name="VendorID" id="VendorID"/>'
    . '<input type="hidden" name="currencycode" id="currencycode"/></td>'
    . '<td>Supplier Name</td><td><input tabindex="5" type="text" name="VendorName" id="VendorName"/>'
    . '<input type="submit" value="GO"/></td></tr></table></div>';

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
      ,sum(PurchaseLine.[invoiceamount]) as OrderValue
      ,[PurchaseHeader].printed
      from [PurchaseHeader] join PurchaseLine on 
      [PurchaseHeader].[documentno]=PurchaseLine.[documentno] 
      where [PurchaseHeader].[documenttype]='18' or [PurchaseHeader].[documenttype]='20'".
        (isset($_POST['VendorID'])?" and [PurchaseHeader].[vendorcode]='".$_POST['VendorID']."'":'')."
       group by 
       [PurchaseHeader].[documentno]
      ,[PurchaseHeader].[docdate]
      ,[PurchaseHeader].[oderdate]
      ,[PurchaseHeader].[duedate]
      ,[PurchaseHeader].[vendorcode]
      ,[PurchaseHeader].[vendorname]
      ,[PurchaseHeader].[currencycode]
      ,[PurchaseHeader].[status]
      ,[PurchaseHeader].[userid]  
      ,[PurchaseHeader].printed 
      order by [PurchaseHeader].[docdate] desc
      limit ". $_SESSION['DefaultDisplayRecordsMax'] ."";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Receive<br />Order</th>' 
             . '<th>Print<br />Order</th>'  
             . '<th>Purchase <br /> Order <br /> Document<br /> date</th>'
             . '<th>Purchase <br /> Order <br />Due <br />Date</th>'
             . '<th>Supplier <br />ID</th>'
             . '<th>Supplier<br /> Name</th>'
             . '<th>Purchase <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Print<br />Status</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
          
            . '</tr>';
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
           
        echo sprintf('<td><a href="%s?ref=%s">Receive :%s</a></td>',
        htmlspecialchars('Goodsrecievedfromorders.php',ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
        
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
              
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',is_null($row['duedate'])?'': ConvertSQLDate($row['duedate']));
        echo sprintf('<td>%s</td>',$row['vendorcode']);
        echo sprintf('<td>%s</td>',$row['vendorname']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['printed']==1?'Has Been Printed':'Not Yet Printed');
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
          
         
        echo '</tr>';
  }
        
    echo '</table><br />';
	
echo '</div></form>';

include('includes/footer.inc');

}   


?>