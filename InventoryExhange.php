<?php
include('includes/session.inc');
$Title = _('Authorise Inventory Exchange');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

 if(isset($_GET['No'])){
   $Selected=$_GET['No'];
}elseif($_POST['SelectedNo']){
   $Selected=$_POST['SelectedNo'];
}

if(isset($_GET['direction'])){
    $direction = $_GET['direction'];
}elseif(isset($_POST['direction'])){
    $direction =$_POST['direction'];
}

echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/inventory.png" title="' . _('Inventory Exchange') .'" alt="" />' .' ' . _('Inventory Exchange') . '</p>';

if(isset($_POST['cancel'])){
    $SQL="Update [stockexchange] set [authorised]=2 where [docno]='".$_POST['SelectedNo']."'";
    DB_query($SQL, $db);
}

if(isset($_POST['submit'])){
    $sql = array();
    $PeriodNo = GetPeriod($_POST['docdate'], $db,false);
    
    $SQLmst="select [COST],[item] from [stockexchange]  where [docno]='".$_POST['SelectedNo']."'";
    $ResultIndex=DB_query($SQLmst, $db);
    $stockexchange=DB_fetch_row($ResultIndex);
    
    
    $sql[]="Update [stockexchange] set [authorised]=1 where [docno]='".$_POST['SelectedNo']."'";
    
    $sql[]="INSERT INTO [stockledger]
      ([date],[doctyp],[itemcode],[invref],[fulqty],[loosqty],[store],[journal],[period])
      (select [docdate],50,[item],[docno],
      (case when [uom]='fulqty' then (qty) else 0 end),
      (case when [uom]='fulqty' then 0 else (qty) end),
      [store],[docno],".$PeriodNo." from [stockexchange]
      where [authorised]=1 and [docno]='".$_POST['SelectedNo']."')";
    
           
          
        if($_POST['direction']==0) {     

            
            if(CheckUOM()=='fulqty'){
              $sql[] = "Update [stockmaster] set [averagestock]=('".$stockexchange[0]."'/partsperunit) where itemcode='".$stockexchange[1]."'";
            }else{
              $sql[] = "Update [stockmaster] set [averagestock]='".$stockexchange[0]."' where itemcode='".$stockexchange[1]."'";
            }
            
            $sql[] = "INSERT INTO [StockRegister] ([itemcode],[StockIn],[cost],[StockOut],[journal],[GRN]) 
                      (select [item],[qty],[COST],0,[docno],[docno] from [stockexchange] 
                      where [authorised]=1 and [docno]='".$_POST['SelectedNo']."')";

                 
            $sql[]=SPRINTF("Insert into [Generalledger]
            ([journalno],[Docdate] ,[period] ,[DocumentNo],[DocumentType] ,[accountcode],[balaccountcode],[VATaccountcode],[amount] ,[VATamount] ,[currencycode] ,[ExchangeRate] ,[narration])
            (select [docno],[docdate] ,'%s' ,[docno] ,50
                    ,[inventorypostinggroup].[defaultgl_purch]
                    ,[arpostinggroups].[creditorsaccount]
                    ,''
                    ,case when [stockexchange].uom='fulqty' 
                    then (([stockexchange].[cost]/[stockmaster].partsperunit) * [stockexchange].qty )
                    else ([stockexchange].[cost] * [stockexchange].qty) end
                    ,0
                    ,[currencies].[currabrev]
                    ,[currencies].[rate]
                    ,(rtrim([stockmaster].[descrip]) +' X '+ cast(qty as char(10))) as narration
                    from [stockexchange] 
                    join [stockmaster] on [stockexchange].[item] = [stockmaster].itemcode
                    join [inventorypostinggroup] on [stockmaster].[postinggroup] = [inventorypostinggroup].[code]
                    join [creditors] on stockexchange.acctfolio = creditors.itemcode
                    join [currencies] on [creditors].[curr_cod] = [currencies].[currabrev]
                    join [arpostinggroups] on [arpostinggroups].[code]=[creditors].[supplierposting] 
                    where [stockexchange].[docno]='%s' )",$PeriodNo,$_POST['SelectedNo']); 
            
             $sql[]=SPRINTF("Insert into [Generalledger]
            ([journalno],[Docdate] ,[period] ,[DocumentNo],[DocumentType] ,[accountcode],[balaccountcode],[VATaccountcode],[amount] ,[VATamount] ,[currencycode] ,[ExchangeRate] ,[narration])
            (select [docno],[docdate] ,'%s' ,[docno] ,50
                    ,[inventorypostinggroup].[balancesheet]
                    ,[inventorypostinggroup].[stockvariance]
                    ,''
                    ,case when [stockexchange].uom='fulqty' 
                    then (([stockexchange].[cost]/[stockmaster].partsperunit) * [stockexchange].qty )
                    else ([stockexchange].[cost] * [stockexchange].qty) end
                    ,0
                    ,[currencies].[currabrev]
                    ,[currencies].[rate]
                    ,(rtrim([stockmaster].[descrip]) +' X '+ cast(qty as char(10))) as narration
                    from [stockexchange] 
                    join [stockmaster] on [stockexchange].[item] = [stockmaster].itemcode
                    join [inventorypostinggroup] on [stockmaster].[postinggroup] = [inventorypostinggroup].[code]
                    join [creditors] on stockexchange.acctfolio = creditors.itemcode
                    join [currencies] on [creditors].[curr_cod] = [currencies].[currabrev]
                    where [stockexchange].[docno]='%s' )",$PeriodNo,$_POST['SelectedNo']); 
            
        }else{ 
    
            $hasEmpties=Checkcontainer($_POST['SelectedNo']);
            if($hasEmpties==1){
                
               $sql[]="INSERT INTO [stockledger]
                  ([date],[doctyp],[itemcode],[invref],[fulqty],[loosqty],[store],[journal],[period])
                  (select [docdate],50,[stockmaster].[container],[docno],
                  (case 
                    when [uom]='fulqty'  then (qty) 
                    else 0 
                  end), 0,[store],[docno],".$PeriodNo."  from [stockexchange]
                  join [stockmaster] on [stockexchange].[item]=[stockmaster].itemcode
                  where [authorised]=1 and [docno]='".$_POST['SelectedNo']."')";
                
                $sql[]= GetEmptyFIFO($PeriodNo,$_POST['SelectedNo']);
                 
            }
            $sql[]= GetFIFO($PeriodNo,$_POST['SelectedNo']); 
           
        }
        
      
     DB_Txn_Begin($db);
     foreach ($sql as $value) {
        DB_query($value,$db);
     }
     
     if(DB_error_no($db)>0){       
         DB_Txn_Rollback($db);
         prnMsg('The exhange failed to save','warn');
     } else{
         DB_Txn_Commit($db);
         prnMsg('The exhange has been completed successfully');
     }
    
}

  echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post">';
  echo '<div class="container">';
  echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
  
if(isset($Selected)){
    
   echo '<input type="hidden" name="SelectedNo" value="'.$Selected.'" />';
   echo '<input type="hidden" name="direction" value="'.$direction.'" />';
        
   $SQL="SELECT [docdate]
      ,[docno]
      ,[item]
      ,[uom]
      ,[store]
      ,[qty]
      ,[userid]
      ,creditors.customer as acctfolio
      ,[reason]
      ,[authorised]
      ,[authorisedby]
      ,[authoriseddate]
    FROM [stockexchange] join creditors on stockexchange.acctfolio=creditors.itemcode
    where [authorised]=0
    and [docno]='".$Selected."' order by docdate desc";
  
   $ResultIndex=DB_query($SQL,$db);
   $ResultsSet=DB_fetch_array($ResultIndex);
   
   echo '<table  class="table table-bordered"><tr><th></th><th></th></tr>';
   echo '<tr><td>Document Date:</td>'
      . '<td><input type="text" readonly="readonly" name="docdate" value="'.ConvertSQLDate($ResultsSet['docdate']).'"/></td></tr>';
   echo '<tr><td>Document No:</td><td>'.$ResultsSet['docno'].'</td></tr>';
   echo '<tr><td>Issue/Receive</td>'
    . '<td>'. GetStockName($ResultsSet['item']).'</td>'
    . '<td>'. number_format($ResultsSet['qty'],0).'</td>'
    . '<td>'. getuomdesc($ResultsSet['itemfrom'],$ResultsSet['uomfrom']).'</td></tr>';
   echo '<tr><td>Source:</td><td>'.$ResultsSet['acctfolio'].'</td></tr>';
   echo '<tr><td>Reason:</td><td>'.$ResultsSet['reason'].'</td></tr>';
   echo '</table>' ;
   echo '<div><input type="submit" name="submit" value="Approve And Exchange" onclick="return confirm(\''._('Do you want to Approve these details ?').'\');"/></div>';
   echo '<div><input type="submit" name="cancel" value="Cancel"  onclick="return confirm(\''._('Do you want to cancel these details ?').'\');"/></div>';
  
  
}  

    $SQL="SELECT [docdate],[docno],[item],[uom],[store],[qty] ,[userid] ,[creditors].[customer] as acctfolio ,[reason],[authorised],[authorisedby],[authoriseddate] ,[direction]
    FROM [stockexchange] join creditors on stockexchange.acctfolio=creditors.itemcode
    where [authorised]=0";
          
  echo '<table class="table table-bordered"><tr>'
          . '<th>Document<br/> No</th>'
          . '<th>Document<br/> Date</th>'
          . '<th>Source</th>'
          . '<th>Reason</th>'
          . '<th>Posted<br/> By</th>'
          . '</tr>';
  
  $ResultIndex=DB_query($SQL,$db);
  while($rows=DB_fetch_array($ResultIndex)){
      echo sprintf('<tr><td><a href="%s?No=%s&direction=%s">View :%s</a></td>',$_SERVER['PHP_SELF'],$rows['docno'],$rows['direction'],$rows['docno']);
      echo sprintf('<td>%s</td>',ConvertSQLDate($rows['docdate']));
      echo sprintf('<td>%s</td>',$rows['acctfolio']);
      echo sprintf('<td>%s</td>',$rows['reason']);
      echo sprintf('<td>%s</td></tr>',$rows['userid']);
  }
  
  echo '</table></div>';
  echo '</form>' ;


include('includes/footer.inc');

function dropprocedure($procedurename){
global $db;
    $SQL="IF  EXISTS (SELECT * FROM sys.objects WHERE "
            . "object_id = OBJECT_ID(N'[".$procedurename."]') 
                AND type in (N'P', N'PC'))
          DROP PROCEDURE [".$procedurename."]";
    $r=DB_query($SQL,$db);

}

function GetStockName($itemcode){
    global $db;
  
        $sql="Select itemcode,descrip from stockmaster where itemcode='".$itemcode."'";
        $ResultIndex=DB_query($sql,$db);
        $rows=DB_fetch_row($ResultIndex);
        return $rows[1];
        
}

function getuomdesc($stockcode,$uom){
   global $db;
     
      $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
      FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code 
      left join [unit] l on [stockmaster].[subunits]=l.code 
      where itemcode= '".$stockcode."'", $db);
      $rows=DB_fetch_row($REsults);
        
      switch ($uom) {
          case 'fulqty':
              $return = $rows[0];
              break;          
          default:
              $return = $rows[1];
              break;
      }
      
      return $return;
}


function Checkcontainer($REF){
    Global $db;
    
     $sqlstmt="select [stockmaster].[container] from [stockexchange] 
             join [stockmaster] on [stockexchange].[item]=[stockmaster].itemcode
            where [authorised]=1 and [docno]='".$REF."'";
     
     $REsults = DB_query($sqlstmt,$db);
     $rows = DB_fetch_row($REsults);
    
     return (mb_strlen($rows[0])>0?1:0) ;
    
}

function CheckUOM(){
    Global $db;
    
     $sqlstmt="select [uom] from [stockexchange] 
          where [authorised]=1 and [docno]='".$_POST['SelectedNo']."'";
     
     $REsults=DB_query($sqlstmt,$db);
     $rows=DB_fetch_row($REsults);
        
    return $rows[0];
      
}


Function GetEmptyFIFO($PeriodNo,$SelectedNo){
 global $db;

$procodedure ="Create PROCEDURE [EMPTYFIFO]
    AS
    BEGIN
    
        DECLARE @itemcode [char](20),
        @quantity int,
        @usequantity int,
        @usecost float,
        @rowid float,
        @avecost float,
        @stockbalance float,
        @partperunit int,
        @UOM varchar(10),
        @stcode varchar(10),
        @qtyinput float
        
      select 
            @stcode   = [stockmaster].[container],
            @UOM      = [stockexchange].[uom] ,
            @qtyinput = [stockexchange].qty 
      from [stockexchange]  
      join [stockmaster] on [stockexchange].[item] = [stockmaster].itemcode
      where [stockexchange].[authorised]=1 
      and [stockexchange].[docno] ='".$SelectedNo."'
        
     Select @partperunit = partperunit from stockmaster where itemcode=@stcode
   
	DECLARE STOCKREGISTER CURSOR FOR 
        select [cost],[StockBalance],[rowid]  from [StockRegister] 
        where [itemcode]=@stcode and [StockBalance]>0  order by rowid asc
        
        open STOCKREGISTER
        FETCH NEXT FROM STOCKREGISTER into @avecost,@stockbalance,@rowid
        WHILE (@@FETCH_STATUS = 0)
        BEGIN 
    
            if(@quantity>@stockbalance) 
            begin
                Update [StockRegister] set [StockOut]=([StockOut] + @stockbalance) where rowid=@rowid
                select @usequantity = @stockbalance
                select @quantity = (@quantity-@usequantity)
            end
            else
            begin 
                Update [StockRegister] set [StockOut]=([StockOut] + @quantity) where rowid=@rowid
                select @usequantity = @quantity
                select @quantity  = 0
            end
            
       
if(@usequantity>0)
begin

        Insert into [Generalledger]
                   ([journalno],[Docdate] ,[period] ,[DocumentNo],[DocumentType] ,[accountcode],[balaccountcode]
                   ,[VATaccountcode],[amount] ,[VATamount] ,[currencycode] ,[ExchangeRate] ,[narration])
                   (select
                         [docno],[docdate] ,".$PeriodNo.", [docno],'50'
                        ,[inventorypostinggroup].[stockvariance]
                        ,[inventorypostinggroup].[balancesheet]
                        ,''
                        ,case 
                        when [stockexchange].uom='fulqty' 
                        then (([E].[averagestock]) * @usequantity) 
                        else (0) end
                        ,0
                        ,[currencies].[currabrev]
                        ,[currencies].[rate]
                        ,(rtrim([E].[descrip]) + ' X '+ cast(abs([stockexchange].qty) as char(10))) as narration
                        from [stockexchange] 
                        join [stockmaster] on [stockexchange].[item] = [stockmaster].itemcode
                        join [stockmaster] E on [stockmaster].[container] = [E].[itemcode]
                        join [inventorypostinggroup] on [stockmaster].[postinggroup] = [inventorypostinggroup].[code]
                        join [creditors] on stockexchange.acctfolio = creditors.itemcode
                        join [currencies] on [creditors].[curr_cod] = [currencies].[currabrev]
                        where [stockexchange].[docno]='".$SelectedNo."' )
                            
end



  
FETCH NEXT FROM  STOCKREGISTER into @avecost,@stockbalance,@rowid
END
CLOSE  STOCKREGISTER
DEALLOCATE  STOCKREGISTER

END";
    
  dropprocedure('EMPTYFIFO');
  DB_query($procodedure, $db);
  return 'EXECUTE EMPTYFIFO';
}

Function GetFIFO($PeriodNo,$SelectedNo){
 global $db;

$procodedure ="Create PROCEDURE [FIFO]
    AS
    BEGIN
    
        DECLARE @itemcode [char](20),
        @quantity int,
        @usequantity int,
        @usecost float,
        @rowid float,
        @avecost float,
        @stockbalance float,
        @partperunit int,
        @UOM varchar(10),
        @stcode varchar(10),
        @qtyinput float
        
      
          
   select 
            @stcode   = [stockmaster].[container],
            @UOM      = [stockexchange].[uom] ,
            @qtyinput = (case when [stockexchange].[uom]='fulqty' 
            then ([stockexchange].[qty] * [stockmaster].partperunit) 
            else ([stockexchange].[qty]) end )
      from [stockexchange]  
      join [stockmaster] on [stockexchange].[item] = [stockmaster].itemcode
      where [stockexchange].[authorised]=1 
      and [stockexchange].[docno] ='".$SelectedNo."'
        
        
        Select @partperunit = partperunit from stockmaster where itemcode=@stcode
        select @quantity=  abs(@qtyinput)
   
	DECLARE STOCKREGISTER CURSOR FOR select [cost],[StockBalance],[rowid] 
        from [StockRegister] where [itemcode]=@stcode and [StockBalance]>0  order by rowid asc
        open STOCKREGISTER
        FETCH NEXT FROM STOCKREGISTER into @avecost,@stockbalance,@rowid
        WHILE (@@FETCH_STATUS = 0)
        BEGIN 
    
            if(@quantity>@stockbalance) 
            begin
                Update [StockRegister] set [StockOut]=([StockOut] + @stockbalance) where rowid=@rowid
                select @usequantity = @stockbalance
                select @quantity = (@quantity - @usequantity)
            end
            else
            begin 
                Update [StockRegister] set [StockOut]=([StockOut] + @quantity) where rowid=@rowid
                select @usequantity = @quantity
                select @quantity  = 0
            end
            
      
if(@usequantity>0)
begin
                    Insert into [Generalledger]
                   ([journalno],[Docdate] ,[period] ,[DocumentNo],[DocumentType] ,[accountcode],[balaccountcode]
                   ,[VATaccountcode],[amount] ,[VATamount] ,[currencycode] ,[ExchangeRate] ,[narration])
                   (select
                         [docno],[docdate],".$PeriodNo.",[docno],'50'
                        ,[arpostinggroups].[creditorsaccount]
                        ,[inventorypostinggroup].[defaultgl_sales]
                        ,''
                        ,(case when [stockexchange].uom='fulqty' 
                        then (([stockexchange].[cost]/[stockmaster].partperunit) * @usequantity )
                        else ([stockexchange].[cost] * @usequantity ) end)
                        ,0
                        ,[currencies].[currabrev]
                        ,[currencies].[rate]
                        ,(rtrim([stockmaster].[descrip]) + ' X '+ cast(abs(qty) as char(10))) as narration
                        from [stockexchange] 
                        join [stockmaster] on [stockexchange].[item] = [stockmaster].itemcode
                        join [inventorypostinggroup] on [stockmaster].[postinggroup] = [inventorypostinggroup].[code]
                        join [creditors] on [stockexchange].[acctfolio] = [creditors].[itemcode]
                        join [currencies] on [creditors].[curr_cod] = [currencies].[currabrev]
                        join [arpostinggroups] on [arpostinggroups].[code]=[creditors].[supplierposting] 
                        where [stockexchange].[docno]='".$SelectedNo."' )
                            

   Insert into [Generalledger]
                   ([journalno],[Docdate] ,[period] ,[DocumentNo],[DocumentType] ,[accountcode],[balaccountcode]
                   ,[VATaccountcode],[amount] ,[VATamount] ,[currencycode] ,[ExchangeRate] ,[narration])
                   (select
                         [docno],[docdate],".$PeriodNo.",[docno],'50'
                        ,[inventorypostinggroup].[stockvariance]
                        ,[inventorypostinggroup].[balancesheet]
                        ,''
                        ,case when [stockexchange].uom='fulqty' 
                        then (([stockexchange].[cost]/[stockmaster].partperunit) * @usequantity )
                        else ([stockexchange].[cost] * @usequantity ) end
                        ,0
                        ,[currencies].[currabrev]
                        ,[currencies].[rate]
                        ,(rtrim([stockmaster].[descrip]) + ' X '+ cast(abs(qty) as char(10))) as narration
                        from [stockexchange] 
                        join [stockmaster] on [stockexchange].[item] = [stockmaster].itemcode
                        join [inventorypostinggroup] on [stockmaster].[postinggroup] = [inventorypostinggroup].[code]
                        join [creditors] on stockexchange.acctfolio = creditors.itemcode
                        join [currencies] on [creditors].[curr_cod] = [currencies].[currabrev]
                        where [stockexchange].[docno]='".$SelectedNo."' )
                            
end



  
FETCH NEXT FROM  STOCKREGISTER into @avecost,@stockbalance,@rowid
END
CLOSE  STOCKREGISTER
DEALLOCATE  STOCKREGISTER

END";
    
 dropprocedure('FIFO');
 DB_query($procodedure, $db);
 return 'EXECUTE FIFO';
}

?>

