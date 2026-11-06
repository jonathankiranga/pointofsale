<?php
$_SESSION['DocumentNo']=$_POST['documentno'] ;
$_SESSION['invoicerrors']=0;
$_SESSION['somedetails']=0;

foreach ($_POST['subunits'] as $key => $value) {
       
    $location = $_POST['location'][$key];
    $stcode = $_POST['code'][$key] ;
    $qty = $_POST['subunits'][$key] ;
    $dbbalance = $_POST['balance'][$key];
    
    if($qty > 0){
        $_SESSION['invoicerrors']++;
    }
    
    
    if($dbbalance < 0){
        $_SESSION['somedetails']++;
    } 
    
    
}


    
if($_SESSION['somedetails'] > 0) {
        prnMsg('You cannot return more than was ordered','warn');
    }
    
 if($_SESSION['invoicerrors'] == 0){
       prnMsg('You have not entred stock units to return','warn');
}   
    

if($_SESSION['DocumentPicking']==true){
      prnMsg('Create a new Debit Note','warn');
}

if($_SESSION['somedetails'] == 0 AND $_SESSION['DocumentPicking']==false){
    
    $ResultIndex = DB_query('Select sysdate() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
       
    $sqldebtors=DB_query("SELECT [itemcode],[customer],[phone],
    [email],[city],[country],[curr_cod],[supplierposting],[VATinclusive]
    FROM [creditors] join arpostinggroups on code=[supplierposting] 
    where itemcode='".$_POST['VendorID']."'", $db);
    
    $debtorsrow = DB_fetch_row($sqldebtors);
    $customerposting = $debtorsrow[7];
    $VATinclusive    = $debtorsrow[8];
    $PeriodNo        = GetPeriod($_POST['posingdate'],$db,false);
    $TransBuffer     = DB_Txn_Begin($db);
    $INVOICENO       = GetNextTransNo(21,$db);
    $journal         = GetNextTransNo(0,$db);
    
    $sql=array();
    
    
    
    
    $sql[] ="insert into [PurchaseHeader] 
      ([documenttype]
      ,[documentno]
      ,[docdate]
      ,[postingdate]
      ,[vendorcode]
      ,[vendorname]
      ,[yourreference]
      ,[externaldocumentno]
      ,[postinggroup]
      ,[currencycode]
      ,[printed]
      ,[released]
      ,[status]
      ,[userid]
      ,[period]
      ,[vatinclusive]) 
      (select 
      21
      ,'".$INVOICENO."'
      ,'".FormatDateForSQL($_POST['date'])."'
       ,'".FormatDateForSQL($_POST['posingdate'])."'
       ,'".$_POST['VendorID']."'
       ,'".$_POST['VendorName']."'
      ,'".$_POST['reference']."'
      ,'".$_SESSION['DocumentNo']."'
      ,'".$customerposting."'
      ,'".$_POST['currencycode']."'
      ,0
      ,0
      ,1
      ,'".$_SESSION['UserID']."'
      ,'".$PeriodNo."'
      ,'".$VATinclusive ."'
      from [PurchaseHeader] 
      where documentno='".$_SESSION['DocumentNo']."') ";

    
    $stockCostPostingFailed = false;

    foreach ($_POST['subunits'] as $key => $value) {
       
    if($value>0){

    $location = $_POST['location'][$key];
    $stcode = $_POST['code'][$key];
    $qty = $_POST['subunits'][$key];       
    $salesoderno= $_SESSION['DocumentNo'];
               
               
            
 $sql[]= sprintf("Update [PurchaseLine] set
      [Qunatity_delivered]= IFNULL([Qunatity_delivered],0)- %f,
      [Quantity_toinvoice]= [Quantity]-[Qunatity_delivered]
      where [documentno]='%s' and [documenttype]='%s' 
      and [entryno]=%s",$qty,$salesoderno,'18',$key);
              

              $sql[] =sprintf("INSERT INTO [PurchaseLine]
              ([documenttype],[docdate],[documentno],[code],[description],[unitofmeasure],[Quantity],[UnitPrice],[vatamount]
              ,[invoiceamount],[vatrate],[inclusive],[locationcode],UOM) 
              (select '%s','%s','%s',[code],[description],[unitofmeasure],%f,%f,%f,%f,[vatrate],[inclusive],'%s',[UOM] 
              from [PurchaseLine] where [PurchaseLine].[entryno]='%s')",'21',FormatDateForSQL($_POST['date']),$INVOICENO
              ,$_POST['subunits'][$key]
              ,$_POST['salesprice'][$key]
              ,$_POST['vatamount'][$key]
              ,$_POST['grossamount'][$key]
              ,$_POST['location'][$key]
              ,$key);
              
            
                $sql[]="INSERT [stockledger]
                    ([date],[stname] ,[doctyp] ,[itemcode],[invref],[netamt] ,[vat] ,[amount] ,fulqty,[loosqty],[price],[store] ,[journal] ,[period] ,jobcard)
                    (SELECT 
                        [PurchaseLine].[docdate]
                       ,[PurchaseLine].[description]
                       ,'30'
                       ,[PurchaseLine].[code]
                       ,[PurchaseLine].[documentno]
                       ,[PurchaseLine].[invoiceamount]
                       ,[PurchaseLine].[vatamount]
                       ,[PurchaseLine].[invoiceamount]
                       ,case [PurchaseLine].UOM when 'fulqty' then ".(0-$qty)."  else 0 end as fulqty
                       ,case [PurchaseLine].UOM when 'loosqty' then ".(0-$qty)."  else 0 end as loosqty
                       ,[PurchaseLine].[UnitPrice]
                       ,'".$location."'
                       ,'".$journal."'
                       ,'".$PeriodNo."'
                       ,'".$INVOICENO ."' 
            FROM [PurchaseLine] where [entryno]='".$key."' and code='".$stcode."')";
              if(PostDebitRegister($qty,$key,$journal,$PeriodNo) === false){
                  $stockCostPostingFailed = true;
              }
     
                
         }
    }
    
   
 
    if($stockCostPostingFailed){
        DB_Txn_Rollback($db);
        prnMsg('The supplier return exceeds the available FIFO costed stock. No debit note was posted.','warn');
        return;
    }

    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error , Contact your administrator','warn');
        
    }else{
        DB_Txn_Commit($db);
        prnMsg('This DEBIT NOTE document '.$INVOICENO.' has been created'.'<a href="PDFPrintdebitnote.php?No='.$INVOICENO.'">DEBIT note Reports</a>');
         $_SESSION['DocumentPicking']=true;
         $_SESSION['DocumentNo']=true;
        unset($_POST);
    }
    
    

}
?>