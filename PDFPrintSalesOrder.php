<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Sales Order');

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
      ,[salesheader].[yourreference]
      from [salesheader] join salesline on 
      [salesheader].[documentno]=salesline.[documentno] 
      join debtors on [salesheader].[customercode]=debtors.itemcode
      where [salesheader].[documenttype]='1' 
      and [salesheader].[documentno]='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    
    
    $PaperSize='A4_Landscape';
    include('includes/PDFStarter.php');
    $headerName="ORDER";
    
    $pdf->addInfo('Title',_('Sales Order'));
    $pdf->addInfo('Subject',_('Sales Order'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFsalesorderheader.inc');
     
     /*
      * (case [salesline].UOM when 'fulqty' 
          then (select salesline.UnitPrice*stockmaster.partperunit from stockmaster where  stockmaster.itemcode=salesline.code) 
	  else (salesline.UnitPrice)
	  end) as Sp 
      */
     $SQL=Sprintf("SELECT 
       stockmaster.barcode
       ,Container.[descrip] as containername
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
      ,salesline.vatamount
      ,salesline.invoiceamount-salesline.vatamount as netamt
      ,salesheader.[documenttype]
      ,salesline.vatrate
      ,salesline.[totalchargedcontainers]
  FROM [salesheader] join salesline 
        on salesheader.documentno=salesline.documentno 
        and salesheader.documenttype=salesline.documenttype and salesheader.documenttype=1
        join stockmaster on stockmaster.itemcode=salesline.code
        left join stockmaster Container on Container.itemcode=salesline.containercode
        where salesheader.documentno='%s' ",$_GET['No']);
     
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
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,100, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(362, $YPos, 85, $FontSize, $rows['UOMDesc'],'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, $rows['Quantity'],'right');
          $LeftOvers = $pdf->addTextWrap(488, $YPos, 85, $FontSize, number_format($rows['Sp'],2),'right');
          $LeftOvers = $pdf->addTextWrap(590, $YPos, 55, $FontSize, number_format($rows['vatrate'],0),'right');
          $LeftOvers = $pdf->addTextWrap(650, $YPos, 70, $FontSize, number_format($rows['vatamount'],2),'right');
          $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$YPos,90,$FontSize,number_format($rows['netamt'],2),'right');

        
         
         $YPos -= $line_height * 2;
         if($YPos< $lastrow){
             $PageNumber++;
             include('includes/PDFsalesorderheader.inc');
              $YPos=$firstrowpos;
         }
        
        
     }
            
     $zYpos = $Bottom_Margin+80;
        $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R2,2),'right');
        $zYpos -=$line_height *2.5;
        $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R1,2),'right');
        $zYpos -=$line_height *2.5 ;
        $LeftOvers = $pdf->addTextWrap($Page_Width-$Right_Margin-100,$zYpos,90,$FontSize, number_format($R3,2),'right');
        
       
        if(mb_strlen($rows['containername'])>0){
        $footer = sprintf("NOTE:%s Are Charged Seperately",$rows['containername']);
        $LeftOvers = $pdf->addTextWrap(42,$Bottom_Margin+80,1000,10,$footer,'left');
        }
        
    $pdf->OutputD($_SESSION['DatabaseName'] . '_ORDER_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
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
      ,[salesheader].[userid] ,
      sum(salesline.[invoiceamount]) as OrderValue
      from [salesheader] join salesline on 
      [salesheader].[documentno]=salesline.[documentno] 
      where [salesheader].[documenttype]='1' 
       group by 
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
      order by 
      [salesheader].[docdate] desc
   limit ". $_SESSION['DefaultDisplayRecordsMax'] ."   ";
    $Result=DB_query($SQL,$db);
       
    Echo '<Table class="table table-bordered"><tr>'
             . '<th>Order <br />No</th>' 
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