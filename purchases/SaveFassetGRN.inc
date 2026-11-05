<?php
$_SESSION['DocumentNo']=$_POST['documentno'] ;
$_SESSION['invoicerrors']=0;
$_SESSION['somedetails']=0;

foreach ($_POST['subunits'] as $key => $value) {
       
    $location = $_POST['location'][$key];
    $stcode = $_POST['code'][$key] ;
    $qty = $_POST['subunits'][$key] ;
    $dbbalance = $_POST['balance'][$key];
    $ordered = $_POST['ordered'][$key] - ($qty+$dbbalance) ;
    
    if($qty > 0){
        $_SESSION['invoicerrors']++;
    }
    
    
    if($ordered < 0){
        $_SESSION['somedetails']++;
    } 
    
    
}
    
if($_SESSION['somedetails'] > 0) {
        prnMsg('You cannot Receive more than was ordered','warn');
    }
    
 if($_SESSION['invoicerrors'] == 0){
       prnMsg('You have not entred stock units to receive','warn');
}   
    

if($_SESSION['DocumentPicking']==true){
      prnMsg('Create a new GRN no','warn');
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
    $INVOICENO       = GetNextTransNo(41,$db);
    $journal         = GetNextTransNo(0,$db);
    
    $sql=array();
    
    
    $sql[]="INSERT INTO [AssetsHeader]
           ([documenttype]
           ,[documentno]
           ,[docdate]
           ".(Is_date($_POST['Salesoderdate'])?',[oderdate]':'')."
           ".(Is_date($_POST['datedue'])?',[duedate]':'')."
           ,[vendorcode]
           ,[vendorname]
           ,[yourreference]
           ,[locationcode]
           ,[postinggroup]
           ,[currencycode]
           ,[status]
           ,[userid]
           ,[period]
           ,[vatinclusive]
           ,[Dimension_1]
           ,[Dimension_2])
     VALUES
           ('41'
           ,'".$INVOICENO ."'
           ,'".FormatDateForSQL($_POST['date'])."'
           ".(Is_date($_POST['Salesoderdate'])?",'".FormatDateForSQL($_POST['Salesoderdate'])."'":'')."
           ".(Is_date($_POST['datedue'])?",'".FormatDateForSQL($_POST['datedue'])."'":'') ."
           ,'".$_POST['VendorID']."'
           ,'".$_POST['VendorName']."'
           ,'".$_POST['reference']."'
           ,'".$_POST['locationcode']."'
           ,'".$customerposting ."'
           ,'".$_POST['currencycode']."'
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,'".$VATinclusive."'
           ,'".$_POST['DimensionOne']."'
           ,'".$_POST['DimensionTwo']."')" ;


    
    foreach ($_POST['subunits'] as $key => $value) {
       
            if($value>0){

               $location = $_POST['location'][$key];
               $stcode = $_POST['code'][$key];
               $qty = $_POST['subunits'][$key];       
               $salesoderno= $_SESSION['DocumentNo'];
            
               $sql[] = sprintf("Update [FixedAssetsLine] set
                [Qunatity_delivered] = (IFNULL([Qunatity_delivered],0) + %f),
                [Quantity_toinvoice] = ([Quantity]-[Qunatity_delivered])
                where [documentno]='%s' and [documenttype]='%s' and [entryno] = %s",$qty,$salesoderno,18,$key);
                    
                    
                $sql[] =  sprintf("INSERT INTO [FixedAssetsLine]
                ([documenttype],[docdate],[documentno],[code],[description],[unitofmeasure],[Quantity],[UnitPrice],[vatamount],[invoiceamount],vatrate,inclusive,UOM) 
                (select '%s','%s','%s',[code],[description],[unitofmeasure],'%s','%s','%s','%s',vatrate,inclusive,UOM from [FixedAssetsLine] 
                 where [entryno]=%s and [documentno]='%s' and [documenttype]='%s')","41",
                FormatDateForSQL($_POST['date']),$INVOICENO,$qty,$_POST['salesprice'][$key],
                $_POST['vatamount'][$key],$_POST['grossamount'][$key],$key,$salesoderno,18 );
              
              
              
                    $sql[]="INSERT INTO fixedassettrans 
                               (assetid,
                                transtype,
                                transno,
                                transdate,
                                periodno,
                                inputdate,
                                fixedassettranstype,
                                amount,
                                units)
                            (SELECT 
                               [FixedAssetsLine].[code],
                               41,
                               '".$INVOICENO."',
                               [FixedAssetsLine].[docdate],
                               '".$PeriodNo."',
                               [FixedAssetsLine].[docdate],
                               '" . _('cost') . "',
                               ([FixedAssetsLine].[invoiceamount]*[currencies].[rate]),
                               '".$qty."'
                               FROM [FixedAssetsLine] 
                               join [AssetsHeader] on [AssetsHeader].[documentno]=[FixedAssetsLine].[documentno]
                               join [currencies] on [currencies].[currabrev]=[AssetsHeader].[currencycode]
                               where [FixedAssetsLine].[documentno]='".$salesoderno."' "
                            . " and [FixedAssetsLine].[code]='".$stcode."' "
                            . " and [FixedAssetsLine].[entryno]='".$key."')";
                
                
                
                
         }
    }
    
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error , Contact your administrator','warn');
        
    } else {
        DB_Txn_Commit($db);
        prnMsg('This GRN document '.$INVOICENO.' has been created'.'<a href="PDFPrintAssetgrn.php?No='.$INVOICENO.'">GRN Reports</a>');
        
         DB_query("Update AssetsHeader set [released]=1 where documentno='".$_SESSION['DocumentNo']."'",$db);

         $_SESSION['DocumentPicking']=true;
         $_SESSION['DocumentNo']=true;
        unset($_POST);

    }
    
    

}
?>