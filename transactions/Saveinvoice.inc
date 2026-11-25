<?php

if (!defined('POS_ACCOUNTING_HELPERS_ONLY')) {

$_SESSION['DocumentNo']=$_POST['documentno'] ;
if($_SESSION['DocumentPosted']==true){
    prnMsg('You cannot repost this invoice','warn');
}else{

    $ResultIndex = DB_query('Select sysdate() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);

    $sqldebtors=DB_query("SELECT [itemcode],[creditlimit],[customer],[phone],
    [email],[city],[country],[curr_cod],[customerposting],[salesman],[VATinclusive]
    FROM [debtors] join postinggroups on code=[customerposting]
    where itemcode='".$_POST['CustomerID']."'", $db);

    $debtorsrow      = DB_fetch_row($sqldebtors);
    $customerposting = $debtorsrow[8];
    $salesrep        = $debtorsrow[9];
    $VATinclusive    = (int)$debtorsrow[10];
    $PeriodNo        = GetPeriod($_POST['date'],$db,false);
    $discount        = ($_POST['discount'] * 0.01);
    $TransBuffer     = DB_Txn_Begin($db);
    $INVOICENO       = GetNextTransNo(31,$db); // shoulb be 10 but 10 is Lab batch
    $sql = array();

    $byBatchInt = ($_SESSION['bybatchno'] === true || $_SESSION['bybatchno'] === 'true' || $_SESSION['bybatchno'] === 1 || $_SESSION['bybatchno'] === '1') ? 1 : 0;

    $sql[] ="insert into [salesheader]
      ([documenttype]
      ,[documentno]
      ,[docdate]
      ,[postingdate]
      ,[customercode]
      ,[customername]
      ,[lpo]
      ,[externaldocumentno]
      ,[postinggroup]
      ,[currencycode]
      ,[salespersoncode]
      ,[printed]
      ,[released]
      ,[status]
      ,[userid]
      ,[period]
      ,[vatinclusive]
      ,[discount]
      ,BatchNO
      ,Bybatch
      ,[paymentterms])
      (select 10
      ,'".$INVOICENO."'
      ,'".FormatDateForSQL($_POST['date'])."'
      ,'".FormatDateForSQL($_POST['posingdate'])."'
      ,'".$_POST['CustomerID']."'
      ,'".$_POST['CustomerName']."'
      ,'".$_POST['reference']."'
      ,'".$_SESSION['DocumentNo']."'
      ,'".$customerposting."'
      ,'".$_POST['currencycode']."'
      ,'".$salesrep."'
      ,0
      ,0
      ,1
      ,'".$_SESSION['UserID']."'
      ,'".$PeriodNo."'
      ,".$VATinclusive."
      ,'".(float)$_POST['discount']."'
      ,'".$_SESSION['batchnoref']."'
      ,".$byBatchInt."
      ,'".$_POST['paymentterms']."'
      from [salesheader]
      where documentno='".$_SESSION['DocumentNo']."') ";

    foreach ($_POST['code'] as $key => $value) {

    if(mb_strlen($value)>0){

    $location    = $_POST['location'][$key];
    $stcode      = $_POST['code'][$key];
    $qty         = $_POST['subunits'][$key];
    $salesprice  = $_POST['salesprice'][$key];
    $Shipping    = $_POST['Shipping'][$key] ;
    $netamount   = $_POST['netamount'][$key];
    $vatamount   = $_POST['vatamount'][$key];
    $grossamount = $_POST['grossamount'][$key];

    $journal = GetNextTransNo(0,$db);

    $sql[] = "Update [salesline] set [completed]=1 where [entryno]='".$key."'";

    $sql[] = sprintf("INSERT INTO [salesline]
           ([documenttype],[docdate],[documentno],[code],[description],[unitofmeasure]
           ,[Quantity],[UnitPrice],[vatamount],[invoiceamount],[vatrate],[inclusive],[locationcode]
           ,UOM,[shipping],BatchNO,Itemlist,sampleID,completed,printed)
           (select '10','%s','%s',[code],[description],[unitofmeasure],[Qunatity_delivered],
           %f ,%f ,%f ,[vatrate],COALESCE([inclusive],0),'%s','fulqty',%f,'%s',Itemlist,sampleID,1,1
           from salesline where [entryno]='".$key."')"
           ,FormatDateForSQL($_POST['date'])
           ,$INVOICENO
           ,$salesprice
           ,$vatamount
           ,$grossamount
           ,$location
           ,$Shipping
           ,$_SESSION['batchnoref']);

    }

    }

    if($discount==0)$discount=1;
    $journal=GetNextTransNo(0,$db);
    $sql[]=PostGL($PeriodNo,$journal,$INVOICENO,$discount);
    $sql[]=PostDebtors($PeriodNo,$journal,$INVOICENO,$discount);
    $sql[]=VATPostGL($PeriodNo,$journal,$INVOICENO,$discount);
    $sql[]=CustomerStatemet($journal,$INVOICENO,$discount);


    foreach ($sql as $value) {
       $ResultIndex=DB_query($value,$db);
    }


    if(DB_error_no($db)>0){
       DB_Txn_Rollback($db);
       prnMsg('There was an Error Posting the invoice, Contact your administrator','warn');
    }else{

        prnMsg('Click here to print invoice No :<a href="PDFPrintSalesInvoice.php?No='.$INVOICENO.'">'.$INVOICENO.'</a>');
        if($_SESSION['bybatchno']=='true'){
           DB_query("Update salesheader set [released]=2 where [documenttype]=1 and [released]=1 and BatchNO='".$_SESSION['batchnoref']."'",$db);
        }else{
           DB_query("Update salesheader set [released]=2 where [documenttype]=1 and [released]=1 and documentno='".$_SESSION['DocumentNo']."'",$db);
        }
        DB_query("Update salesheader set [released]=2 where [documenttype]=10 and [released]=1 and documentno='".$INVOICENO."'",$db);

        DB_Txn_Commit($db);
        prnMsg('This Sale has successfully been posted in the General Ledger');

        $_SESSION['DocumentPosted']=true;
        unset($_SESSION['bybatchno']);
        unset($_SESSION['batchnoref']);
        unset($_SESSION['DocumentNo']);
        unset($_POST);
    }

}

}

function PostDebtors($period,$journal,$doc,$discount){
   $SQL= sprintf("Insert into [debtorsledger]
       ([date]
      ,[details]
      ,[flag]
      ,[invref]
      ,[acctfolio]
      ,[amount]
      ,[pamount]
      ,[curr_cod]
      ,[i_n_t]
      ,[journal]
      ,[typ]
      ,[vatamt]
      ,[systypes_1]
      ,[ledger]
      ,period
      ,cleared
      ,cash)
  (SELECT
      salesheader.[docdate]
      ,COALESCE(NULLIF(TRIM(salesline.sampleID),''),salesline.description,'Sales Invoice') as narration,'I'
      ,salesheader.[documentno]
      ,salesheader.[customercode]
      ,SUM(COALESCE(salesline.invoiceamount,0)) * ".$discount." as Value
      ,0.00
      ,salesheader.[currencycode]
      ,'I'
      ,'%s'
      ,'r'
      ,SUM(COALESCE(salesline.vatamount,0)) * ".$discount." as VAT
      ,salesheader.[documenttype]
      ,salesheader.[postinggroup]
      ,'%s'
      ,0
      ,0
  FROM [salesheader] join salesline
        on salesheader.documentno=salesline.documentno
        and salesheader.documenttype=salesline.documenttype
        where salesheader.documentno='%s' AND salesheader.documenttype=10
        group by
        salesheader.[documenttype]
      ,salesheader.[documentno]
      ,salesheader.[docdate]
      ,salesheader.[customercode]
      ,salesheader.[externaldocumentno]
      ,salesheader.[postinggroup]
      ,salesheader.[currencycode]
      ,salesline.[Quantity]
      ,salesline.sampleID
      ,salesline.[code]
      ,salesline.[UOM])", $journal,$period,$doc);

    return $SQL;
}

function PostGL($period,$journal,$doc,$discount){
    global $db;
    $dimensionOne = isset($_POST['DimensionOne'])
        ? mysqli_real_escape_string($db, (string)$_POST['DimensionOne']) : '';
    $dimensionTwo = isset($_POST['DimensionTwo'])
        ? mysqli_real_escape_string($db, (string)$_POST['DimensionTwo']) : '';
    $sql=SPRINTF("Insert into [Generalledger]
      ([journalno]
      ,[Docdate]
      ,[period]
      ,[DocumentNo]
      ,[DocumentType]
      ,[accountcode]
      ,[balaccountcode]
      ,[VATaccountcode]
      ,[amount]
      ,[VATamount]
      ,[currencycode]
      ,[ExchangeRate]
      ,[cutomercode]
      ,[narration]
      ,[dimension]
      ,[dimension2]
      ,[reconcilled])
      (select
       '%s'
      ,salesheader.[docdate]
      ,'%s'
      ,salesheader.documentno
      ,salesheader.documenttype
      ,[postinggroups].[debtorsaccount]
      ,[inventorypostinggroup].[defaultgl_sales]
      ,[inventorypostinggroup].[defaultgl_vat]
      ,COALESCE(salesline.invoiceamount,0) * ".$discount."
      ,COALESCE(salesline.vatamount,0) * ".$discount." as vatamount
      ,salesheader.[currencycode]
      ,[currencies].[rate]
      ,salesheader.[customercode]
      ,COALESCE(NULLIF(TRIM(salesline.sampleID),''),salesline.description,'Sales Invoice') as narration
      ,'".$dimensionOne."'
      ,'".$dimensionTwo."'
      ,0
      from salesheader
      join [postinggroups] on [salesheader].[postinggroup]=[postinggroups].[code]
      join salesline on [salesline].[documentno] = [salesheader].[documentno]
           and [salesline].[documenttype]=[salesheader].[documenttype]
      join stockmaster on stockmaster.itemcode=salesline.code
      join inventorypostinggroup on inventorypostinggroup.code=stockmaster.postinggroup
      join [currencies] on [salesheader].[currencycode]=[currencies].[currabrev]
      where salesheader.documentno='%s' AND salesheader.documenttype=10
     )",$journal,$period,$doc);

    return $sql;

}

function VATPostGL($period,$journal,$doc,$discount){
    $sql=SPRINTF("Insert into [Generalledger]
      ([journalno]
      ,[Docdate]
      ,[period]
      ,[DocumentNo]
      ,[DocumentType]
      ,[accountcode]
      ,[balaccountcode]
      ,[VATaccountcode]
      ,[amount]
      ,[VATamount]
      ,[currencycode]
      ,[ExchangeRate]
      ,[cutomercode]
      ,[narration]
      ,[reconcilled])
      (select
       '%s'
      ,salesheader.[docdate]
      ,'%s'
      ,salesheader.documentno
      ,salesheader.documenttype
      ,[inventorypostinggroup].[defaultgl_sales]
      ,[inventorypostinggroup].[defaultgl_vat]
      ,''
      ,COALESCE(salesline.vatamount,0) * ".$discount."
      ,0
      ,salesheader.[currencycode]
      ,[currencies].[rate]
      ,salesheader.[customercode]
      ,COALESCE(NULLIF(TRIM(salesline.sampleID),''),salesline.description,'Sales Invoice') as narration
      ,0
      from salesheader join [postinggroups] on [salesheader].[postinggroup]=[postinggroups].[code]
      join salesline on [salesline].[documentno] = [salesheader].[documentno]
           and [salesline].[documenttype]=[salesheader].[documenttype]
      join stockmaster on stockmaster.itemcode=salesline.code
      join inventorypostinggroup on inventorypostinggroup.code=stockmaster.postinggroup
       join [currencies] on [salesheader].[currencycode]=[currencies].[currabrev]
      where salesheader.documentno='%s' AND salesheader.documenttype=10
     )",$journal,$period,$doc);

    return $sql;

}

Function CustomerStatemet($journal,$doc,$discount){
    global $db;
    $dimensionOne = isset($_POST['DimensionOne'])
        ? mysqli_real_escape_string($db, (string)$_POST['DimensionOne']) : '';
    $dimensionTwo = isset($_POST['DimensionTwo'])
        ? mysqli_real_escape_string($db, (string)$_POST['DimensionTwo']) : '';
    $sql=sprintf("INSERT INTO [customerstatement]
           ([Date]
           ,[Documentno]
           ,[Documenttype]
           ,[Accountno]
           ,[Grossamount]
           ,[JournalNo]
           ,[Dimension_One]
           ,[Dimension_Two]
           ,[Currency])
     (select
              salesheader.[docdate]
             ,salesheader.documentno
             ,salesheader.documenttype
             ,salesheader.customercode
             ,sum(COALESCE(salesline.invoiceamount,0)) * ".$discount."
             ,'%s'
             ,'".$dimensionOne."'
             ,'".$dimensionTwo."'
             ,salesheader.[currencycode]
             from salesheader
             join salesline on [salesline].[documentno] = [salesheader].[documentno]
             and [salesline].[documenttype]=[salesheader].[documenttype]
             where salesheader.documentno='%s' AND salesheader.documenttype=10
             Group by
              salesheader.[docdate]
             ,salesheader.documentno
             ,salesheader.documenttype
             ,salesheader.customercode
             ,salesheader.[currencycode]
             ) ",
             $journal,$doc);

    return $sql;
}

/**
 * Post a POS order as a normal ERP sales invoice using the existing ledger
 * posting builders above. The caller owns the database transaction.
 */
function PostPOSInvoice($sourceDocumentNo, $periodNo) {
    global $db;

    $invoiceNo = GetNextTransNo(31, $db);
    $invoiceJournal = GetNextTransNo(0, $db);
    if ($invoiceNo === null || trim((string)$invoiceNo) === '' ||
        !preg_match('/^[A-Za-z0-9._\\/-]+$/', (string)$invoiceNo) ||
        $invoiceJournal === null || trim((string)$invoiceJournal) === '' ||
        !preg_match('/^[A-Za-z0-9._\\/-]+$/', (string)$invoiceJournal)) {
        throw new Exception('Could not allocate a valid POS invoice number or journal number.');
    }

    // Bybatch is TINYINT(1): use integer 0 instead of string 'false' for strict MySQL.
    $headerSql = "INSERT INTO salesheader
        (documenttype,documentno,docdate,postingdate,customercode,customername,
         lpo,externaldocumentno,postinggroup,currencycode,salespersoncode,
         printed,released,status,userid,period,vatinclusive,discount,
         BatchNO,Bybatch,paymentterms)
        SELECT 10,?,src.docdate,NOW(),src.customercode,src.customername,
               'POS',src.documentno,src.postinggroup,src.currencycode,
               COALESCE((SELECT d.salesman FROM debtors d
                         WHERE d.itemcode=src.customercode LIMIT 1),''),
               0,0,1,src.userid,?,COALESCE(src.vatinclusive,0),0,
               COALESCE(src.BatchNO,'POS'),COALESCE(src.Bybatch,0),
               COALESCE(NULLIF(src.paymentterms,''),src.payment_method,'CASH')
        FROM salesheader src
        WHERE src.documenttype=1 AND src.documentno=?";
    $headerStmt = $db->prepare($headerSql);
    if (!$headerStmt) {
        throw new Exception('Could not prepare the POS invoice header: ' . $db->error);
    }
    $periodValue = (int)$periodNo;
    $headerStmt->bind_param('sis', $invoiceNo, $periodValue, $sourceDocumentNo);
    if (!$headerStmt->execute() || $headerStmt->affected_rows !== 1) {
        $error = $headerStmt->error ? $headerStmt->error : 'The source POS sale was not found.';
        $headerStmt->close();
        throw new Exception('Could not create the POS invoice header: ' . $error);
    }
    $headerStmt->close();

    $lineSql = "INSERT INTO salesline
        (entryno,documenttype,docdate,documentno,code,description,unitofmeasure,
         Quantity,UnitPrice,vatamount,invoiceamount,vatrate,inclusive,
         completed,printed,locationcode,UOM,shipping,BatchNO,Itemlist,sampleID)
        SELECT l.entryno,10,l.docdate,?,l.code,l.description,l.unitofmeasure,
               l.Qunatity_delivered,l.UnitPrice,l.vatamount,l.invoiceamount,
               l.vatrate,COALESCE(l.inclusive,0),1,1,l.locationcode,'fulqty',
               COALESCE(l.shipping,0),COALESCE(l.BatchNO,'POS'),
               l.Itemlist,COALESCE(NULLIF(l.sampleID,''),l.description)
        FROM salesline l
        WHERE l.documenttype=1 AND l.documentno=?";
    $lineStmt = $db->prepare($lineSql);
    if (!$lineStmt) {
        throw new Exception('Could not prepare POS invoice lines: ' . $db->error);
    }
    $lineStmt->bind_param('ss', $invoiceNo, $sourceDocumentNo);
    if (!$lineStmt->execute() || $lineStmt->affected_rows < 1) {
        $error = $lineStmt->error ? $lineStmt->error : 'The POS sale has no invoice lines.';
        $lineStmt->close();
        throw new Exception('Could not create POS invoice lines: ' . $error);
    }
    $lineStmt->close();

    // POS has no line/header discount. A multiplier of 1 preserves the
    // validated POS amounts exactly.
    $postingSql = array(
        PostGL($periodNo, $invoiceJournal, $invoiceNo, 1),
        PostDebtors($periodNo, $invoiceJournal, $invoiceNo, 1),
        VATPostGL($periodNo, $invoiceJournal, $invoiceNo, 1),
        CustomerStatemet($invoiceJournal, $invoiceNo, 1)
    );

    foreach ($postingSql as $index => $sql) {
        $result = DB_query($sql, $db, '', '', false, false);
        if (!$result || $db->errno !== 0 || $db->affected_rows < 1) {
            throw new Exception('A POS invoice accounting posting failed (posting step ' . ($index + 1) . '): ' . $db->error);
        }
    }

    $releaseStmt = $db->prepare("UPDATE salesheader SET released=2 WHERE documenttype=10 AND documentno=?");
    if (!$releaseStmt) {
        throw new Exception('Could not prepare POS invoice release: ' . $db->error);
    }
    $releaseStmt->bind_param('s', $invoiceNo);
    if (!$releaseStmt->execute() || $releaseStmt->affected_rows !== 1) {
        $error = $releaseStmt->error ? $releaseStmt->error : 'The POS invoice could not be released.';
        $releaseStmt->close();
        throw new Exception($error);
    }
    $releaseStmt->close();

    return array('documentno' => $invoiceNo, 'journal' => $invoiceJournal);
}

?>
