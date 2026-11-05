<?php
  

$sqldebtors=DB_query("SELECT  [curr_cod],[commissionposting]
  FROM [productionEmployee] where [code]='".$_POST['EmployeeID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$postinggroup = $debtorsrow[1];
$curr_code = $debtorsrow[0];




$transstart = DB_Txn_Begin($db);
$PeriodNo = GetPeriod($_POST['date'],$db,false);
$sql = array();

$sql[]="INSERT INTO [PurchaseHeader]
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
           ,[vatinclusive])
     VALUES
           ('40'
           ,'".$_POST['documentno']."'
           ,'".FormatDateForSQL($_POST['date'])."'
           ".(Is_date($_POST['Salesoderdate'])?",'".FormatDateForSQL($_POST['Salesoderdate'])."'":'')."
           ".(Is_date($_POST['datedue'])?",'".FormatDateForSQL($_POST['datedue'])."'":'') ."
           ,'".$_POST['EmployeeID']."'
           ,'".$_POST['EmployeeName']."'
           ,'".$_POST['reference']."'
           ,'".$_POST['locationcode']."'
           ,'". $postinggroup ."'
           ,'". $curr_code . "'
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,'0')" ;



foreach ($_POST['QTY'] as $itemcode => $value ) {
 
 if($value>0){
        
        
        $SqL="SELECT 
        [stockmaster].[barcode],
        [stockmaster].[itemcode],
        [stockmaster].[descrip] as [stockname],
        [unit].[descrip] as [si],
        [stockmaster].[averagestock],
        [stockmaster].[partperunit]
        FROM [stockmaster]  
        ".(($_POST['UOM'][$itemcode]=='fulqty')?'left join [unit] on [stockmaster].[units]=[unit].[code]':
         'left join [unit] on [stockmaster].[subunits]=[unit].[code]').
        "left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
       where [itemcode]='".$itemcode."'";
    
        $ResultIndex = DB_query($SqL,$db);
        $stkmaster = DB_fetch_row($ResultIndex);
        
        $stockcode = $itemcode;
        $stockname = $stkmaster[2];
        $si        = $stkmaster[3];
        $parts     = $stkmaster[5];      
      
   
        $UOM = $_POST['UOM'][$itemcode];

        if(isset($_POST['QTY'][$itemcode])){
            $qty = $_POST['QTY'][$itemcode];
        }

        if(isset($_POST['RATE_LINE'][$itemcode])){
            $salesprice = $_POST['RATE_LINE'][$itemcode] ;
        }

        if(isset($_POST['TOTAL_LINE'][$itemcode])){
            $grossamount = $_POST['TOTAL_LINE'][$itemcode] ;
        }
    
   
    
    $sql[] =  sprintf("INSERT INTO [PurchaseLine]
           ([documenttype],[docdate],[documentno],[code],[description],[unitofmeasure],[Quantity]
           ,[UnitPrice],[vatamount],[invoiceamount],[vatrate],[inclusive],[UOM]) VALUES
           ('%s','%s','%s','%s','%s','%s',%f,%f,%f,%f,'%s','%s','%s')"
           ,"40"
           ,FormatDateForSQL($_POST['date'])
           ,$_POST['documentno']
           ,$stockcode
           ,$stockname
           ,$si
           ,$qty
           ,$salesprice
           ,0
           ,$grossamount
           ,0
           ,0
           ,$UOM);
        }
        
        
}


if( $_SESSION['locked'] == true){
     foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }

    $_SESSION['savedsuccessfuly']=false;

    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('Failed to save Production Job No :'.$_POST['documentno'],'warn');
    }else{
        DB_Txn_Commit($db);
        prnMsg('new Purchase order :'.$_POST['documentno']);
        $_SESSION['savedsuccessfuly'] = true;
        $_SESSION['locked'] = false;
    }
    
}

if($_SESSION['savedsuccessfuly']==true){
    
    $SQL="select 
       [documentno]
      ,[docdate]
      ,[oderdate]
      ,[duedate]
      ,[postingdate]
      ,[vendorcode]
      ,[vendorname]
      ,[yourreference]
      ,[externaldocumentno]
      ,[locationcode]
      ,[paymentterms]
      ,[postinggroup]
      ,[currencycode]
      ,[printed]
      ,[released]
      ,[status]
      ,[userid] from [PurchaseHeader] 
      where [documenttype]='40' and [documentno]='".$_POST['documentno']."'";
    $Result=DB_query($SQL,$db);
       $row=DB_fetch_row($Result);
    
    Echo '<div class="container"><Table class="table table-bordered">';
        
    echo sprintf("<tr><td>Work Order No</td><td>%s</td></tr>",trim($_POST['documentno']));
    echo sprintf("<tr><td>Work Order Document date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td>Work Order date</td><td>%s</td></tr>", Is_null($row[2])? '':ConvertSQLDate($row[2]));
    echo sprintf("<tr><td>Work Order Due Date</td><td>%s</td></tr>", Is_null($row[3])?'': ConvertSQLDate($row[3]));
    echo sprintf("<tr><td>Employee ID</td><td>%s</td></tr>",$_POST['EmployeeID']);
    echo sprintf("<tr><td>Employee Name</td><td>%s</td></tr>",$_POST['EmployeeName']);
   echo '<tr><td colspan="2">Line Details</td></tr>';
       
    
    echo '<tr><td colspan="2">';
    
    echo '<table class="table table-bordered"><tr>'
            . '<th>Stock Code</th>'
            . '<th>Name</th>'
            . '<th>Unit Of Measure</th>'
            . '<th>Quantity</th>'
            . '<th>Rate</th>'
            . '<th>Gross</th>'
            . '</tr>';
    
    $VATAMOUNT=0;
    $INVOICEAMOUNT=0;
    
    $SQL="select 
           [documenttype]
           ,[docdate]
           ,[documentno]
           ,[code]
           ,[description]
           ,[unitofmeasure]
           ,[Quantity]
           ,[UnitPrice]
           ,[vatamount]
           ,[invoiceamount] from [PurchaseLine] 
           where [documenttype]=40 and [documentno]='".$_POST['documentno']."'";
   
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
      echo sprintf('<tr>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td>%s</td></tr>',
                 $myrow['code'],
                 $myrow['description'],
                 $myrow['unitofmeasure'],
                 $myrow['Quantity'],
                 number_format($myrow['UnitPrice'],2),
                 number_format($myrow['invoiceamount'],2));
                $INVOICEAMOUNT  += $myrow['invoiceamount'];
    }     
    
    echo sprintf('<tfoot><tr><td colspan="5">%s</td><td>%s</td></tr></tfoot>',' Totals',number_format($INVOICEAMOUNT,2));
    echo '</table></td></tr>';
    echo '</table></div>';
    
}
    $_SESSION['Grossamounttotal']=0;
    $_SESSION['CompleteDocument']=$_POST['documentno'];
    
    echo '<div><input type="submit" name="confirm" value="'._('Confirm Production').'"/>';
    echo '<input type="submit" name="Delete" value="'._('Cancel Document').'"/></div>';
?>
