<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.
$Title = _('Print Stock');

$stockClass = new StockBalance();

if(mb_strlen($_POST['fromdate'])>0){
    
    if(mb_strlen($_POST['todate'])>0){
   
        if(isset($_POST['trailbalance'])){
            if(Date1GreaterThanDate2($_POST['fromdate'],$_POST['todate'])==TRUE){
             unset($_POST['trailbalance']);
             $errorExists=1;
            }
        }
        
    }else{
        unset($_POST['trailbalance']);
    }
    
}else{
        unset($_POST['trailbalance']);
    }
 

if(isset($_POST['trailbalance'])){
    
    $PaperSize='A4_Landscape';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Inventory Reports'));
    $pdf->addInfo('Subject',_('inventory'));
    $pdf->addInfo('Creator',_('SmartERP'));
    $store_name = $stockClass ->storename;
    
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFinventoryheader.inc');
     $FontSize = 8;
     $YPos = $firstrowpos;
     $Balance = 0;
     
     $ResultsP = $stockClass->EXECUTE();
     while($rows = DB_fetch_array($ResultsP)){
              
         $LeftOvers = $pdf->addTextWrap(50, $YPos,100, $FontSize, ucfirst($rows['stockname']),'left');
         $LeftOvers = $pdf->addTextWrap(150, $YPos,50, $FontSize, trim($rows['fulqtyName']).'/'.$rows['LoosqtyName'],'right');
         $LeftOvers = $pdf->addTextWrap(230, $YPos,30, $FontSize, number_format($rows['openstock_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(270, $YPos,30, $FontSize, number_format($rows['openstock_l'],0),'right');
         $LeftOvers = $pdf->addTextWrap(310, $YPos,30, $FontSize, number_format($rows['Purchases_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(350, $YPos,30, $FontSize, number_format($rows['Purchases_l'],0),'right');
         $LeftOvers = $pdf->addTextWrap(390, $YPos,30, $FontSize, number_format($rows['Prod_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(430, $YPos,30, $FontSize, number_format($rows['Prod_l'],0),'right');
         $LeftOvers = $pdf->addTextWrap(470, $YPos,30, $FontSize, number_format($rows['Total_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(510, $YPos,30, $FontSize, number_format($rows['total_l'],0),'right');
         $LeftOvers = $pdf->addTextWrap(550, $YPos,30, $FontSize, number_format($rows['Work_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(590, $YPos,30, $FontSize, number_format($rows['Work_l'],0),'right');
         $LeftOvers = $pdf->addTextWrap(630, $YPos,30, $FontSize, number_format($rows['Sales_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(670, $YPos,30, $FontSize, number_format($rows['Sales_l'],0),'right');
         $LeftOvers = $pdf->addTextWrap(710, $YPos,30, $FontSize, number_format($rows['Close_f'],0),'right');
         $LeftOvers = $pdf->addTextWrap(750, $YPos,30, $FontSize, number_format($rows['Close_l'],0),'right');
         $YPos -= $line_height * 0.5 ;
           
         $YPos -= $line_height ;
         if($YPos < ($lastrow+($line_height * 3))){
             include('includes/PDFinventoryheader.inc');
             $YPos=$firstrowpos;
             $FontSize = 8;
         }
      }
          
      DB_query("[update_tank_balance]",$db);
      
 $rowtanks = DB_query("SELECT 
            [ProductionUnit].[itemcode],[capacity],
            [tankname],[UOM],[CapacityUOM] ,
            [ProductionUnit].[units],[balance],
            [stockmaster].[descrip],
            [unit].[descrip] as [si]
        FROM [ProductionUnit] 
        join stockmaster on ProductionUnit.itemcode=stockmaster.itemcode
        left join [unit] on [stockmaster].[units]=[unit].[code]
        where [ProductionUnit].[status]=1", $db);
     
    if(DB_num_rows($rowtanks)>0){
           $YPos -= $line_height ;
           $LeftOvers = $pdf->addTextWrap(50, $YPos,100, $FontSize,_('Work In Progress'),'left');
    }
    
   while($rows = DB_fetch_array($rowtanks)){
       
         $YPos -= $line_height ;
         if($YPos < ($lastrow+($line_height*2))){
             include('includes/PDFinventoryheader.inc');
             $YPos=$firstrowpos;
             $FontSize = 8;
         }
         
         
         $LeftOvers = $pdf->addText(50, $YPos, $FontSize, ucfirst($rows['descrip']),'left');
         $LeftOvers = $pdf->addText(250, $YPos, $FontSize, _('Tank :').$rows['tankname'].$rows['si'],'right');
         $LeftOvers = $pdf->addTextWrap(750, $YPos,30, $FontSize,number_format($rows['units'],0),'right');
     
   }   
    
$pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('Inventory'). '_' . date('Y-m-d').'.pdf');
$pdf->__destruct();

    
} else {
    
  $Title = _('Print Inventory');
  include('includes/header.inc');
  
  if(isset($errorExists)){
     prnMsg('You have selected an invalid date range','warn');
  }
  
  echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/maintenance.png" title="' . _('Inventory Report') .'" alt="" />' . _('Inventory Report') . '</p>';
  echo '<form  action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><div>';
  echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>';
  
  echo '<table class="table table-bordered"><tr><td valign="top"><table class="table table-bordered"><tr>'
    . '<td>From Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="fromdate" size="11" maxlength="10" readonly="readonly" value="' .$_POST['fromdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>
       <td>To Date</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="todate" size="11" maxlength="10" readonly="readonly" value="' .$_POST['todate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
 echo '<tr><td>Select Store</td><td><select name="store"><option value="AllStores">All</option>';
 
   $sql="SELECT [code],[Storename] FROM [Stores]";
   $ResultsP = DB_query($sql,$db);
    while($rows = DB_fetch_array($ResultsP)){
          echo '<option value="'.$rows['code'].'">'.$rows['Storename'].'</option>' ;
   }
   
  echo '</select></td></tr></table></td></tr>'
  . '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Print Inventory"/></td></tr></table>';
  echo '</div></form>';
  
  include('includes/footer.inc');
  
  
}


  
  
  
  class StockBalance {
      var $procedure;
      var $Fromdate;
      var $Todate;
      var $StoreCode;
      var $storename;
      
      function __construct() {
        
          $this->Fromdate = ConvertSQLDateTime($_POST['fromdate']);  
          $this->Todate = ConvertSQLDateTime($_POST['todate']).' 23:59:59';
          $this->StoreCode = $_POST['store'];
          
          if(isset($_POST['store'])){
                if($_POST['store']=='AllStores'){
                     $this->storename='AllStores';
               } else {
                      $sql="SELECT [Storename] FROM [Stores] where [code]='".$_POST['store']."'";
                      $ResultsP = DB_query($sql,$db);
                      $rows=DB_fetch_row($ResultsP);
                      $this->storename=$rows[0];
               }
          }
      }
            
      function CreateScript(){
           global $db;
         
           
        $this->procedure="Create PROCEDURE [InventoryReport]  AS   BEGIN
	
	create table  #inventory (
	[itemcode] [varchar](20) NULL,
	[stockname] [varchar](100) NULL,
	[averagestock] decimal(10,2) NULL,
	[partperunit]  decimal(10,0) NULL,
	[fulqtyName]  [varchar](50) NULL,
	[LoosqtyName] [varchar](50) NULL,
	[openstock_f] decimal(10,0) NULL,
	[openstock_l] decimal(10,0) null,
	[Purchases_f] decimal(10,0) null,
	[Purchases_l] decimal(10,0) null,
        [Prod_f] decimal(10,0) null,
	[Prod_l] decimal(10,0) null,
	[Total_f] decimal(10,0) null,
	[total_l] decimal(10,0) null,
	[Sales_f] decimal(10,0) null,
	[Sales_l] decimal(10,0) null,
        [Work_f] decimal(10,0) null,
	[Work_l] decimal(10,0) null,
	[Close_f] decimal(10,0) null,
	[Close_l] decimal(10,0) null
    )

	Declare @itemcode varchar(20),@stockname varchar(100),@ave float,@kit int,@fulldesc varchar(50),@loosedesc varchar(50)
	Declare @fulqty int ,@loosqty int ,@querry varchar(max), @Openfqty int , @openlsqty int
	Declare @Purchasesfqty int ,@purchaselsqty int ,@Totalfqty int ,@Totallsqty int
	Declare @Salesfqty int ,@Saleslsqty int ,@Closingfqty int ,@Closinglsqty int ,@results varchar(max)
        Declare @Prodfqty int ,@Prodlsqty int ,@Worderqty int , @Worklsqty int
        Declare @Openfqty1 int ,@openlsqty1 int

	Declare Sstock cursor for select stockmaster.itemcode,stockmaster.descrip,stockmaster.averagestock,stockmaster.partperunit,unitfull.descrip,
	unitloos.descrip from stockmaster left join unit unitfull on stockmaster.units=unitfull.code left join unit unitloos on stockmaster.subunits=unitloos.code
	where isstock not in (3)
        
open    Sstock
	fetch next from  Sstock into @itemcode,@stockname,@ave,@kit,@fulldesc,@loosedesc
	WHILE (@@FETCH_STATUS = 0)
	BEGIN 

	select @fulqty=sum(fulqty),@loosqty=sum(loosqty) from stockledger where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and  date <'".$this->Fromdate."' ":" and date < '".$this->Fromdate."' and store= '".$this->StoreCode."'"); 
	
        $this->procedure .=" select @Openfqty1 = IFNULL(@fulqty,0) + CAST(IFNULL(@loosqty,0)/@kit AS int)
	Select @openlsqty1 = (IFNULL(@Openfqty1 * @kit,0) + IFNULL(@loosqty,0)) % @kit

        select @fulqty=sum(fulqty),@loosqty=sum(loosqty) from stockledger  where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and date between '".$this->Fromdate."' and '".$this->Todate."'  and (doctyp=17)":" and date between '".$this->Fromdate."' and '".$this->Todate."'  and store='".$this->StoreCode."' and (doctyp=17)");
        
        $this->procedure .=" select @Openfqty  = (IFNULL(@fulqty,0) + @Openfqty1) + CAST( (IFNULL(@loosqty,0) + @openlsqty1) /@kit AS int)
	Select @openlsqty =( (@Openfqty * @kit) + (IFNULL(@loosqty,0)+ @openlsqty1) ) % @kit
        


	select @fulqty=sum(fulqty),@loosqty=sum(loosqty) from stockledger where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and date between '".$this->Fromdate."' and '".$this->Todate."'  and (doctyp=30)":" and date between '".$this->Fromdate."' and '".$this->Todate."'  and store='".$this->StoreCode."' and (doctyp=30)");
        
        $this->procedure .="  select @Purchasesfqty = IFNULL(@fulqty,0) + CAST(IFNULL(@loosqty,0)/@kit AS int)
	Select @purchaselsqty =((@Purchasesfqty * @kit) + IFNULL(@loosqty,0)) % @kit
        


	select @fulqty=sum(fulqty),@loosqty=sum(loosqty) from stockledger  where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and date between '".$this->Fromdate."' and '".$this->Todate."'  and (doctyp=40)":" and date between '".$this->Fromdate."' and '".$this->Todate."'  and store='".$this->StoreCode."' and (doctyp=40)");
        
        $this->procedure .="  select @Prodfqty = IFNULL(@fulqty,0) + CAST(IFNULL(@loosqty,0)/@kit AS int)
	Select @Prodlsqty=((@Prodfqty * @kit)+IFNULL(@loosqty,0)) % @kit



	select @fulqty=sum(fulqty),@loosqty=sum(loosqty) from stockledger  where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and date between '".$this->Fromdate."' and '".$this->Todate."'  and (doctyp=26)":" and date between '".$this->Fromdate."' and '".$this->Todate."'  and store='".$this->StoreCode."' and (doctyp=26)");
        
        $this->procedure .="  select @Worderqty = IFNULL(@fulqty,0) + CAST(IFNULL(@loosqty,0)/@kit AS int)
	Select @Worklsqty=((@Worderqty * @kit)+IFNULL(@loosqty,0)) % @kit
        



        select @fulqty=sum(fulqty),@loosqty=sum(loosqty) from stockledger  where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and date between '".$this->Fromdate."' and '".$this->Todate."'  and (doctyp=19)":" and date between '".$this->Fromdate."' and '".$this->Todate."'  and store='".$this->StoreCode."' and (doctyp=19)");
        
        $this->procedure .="  select @Salesfqty = IFNULL(@fulqty,0) + CAST(IFNULL(@loosqty,0)/@kit AS int)
	Select @Saleslsqty=((@Salesfqty * @kit)+IFNULL(@loosqty,0)) % @kit


	select  @Totalfqty = @Openfqty+@Purchasesfqty+@Prodfqty + cast((@openlsqty + @purchaselsqty +@Prodlsqty )/@kit as int)
	select  @Totallsqty=((@Totalfqty * @kit)+(@openlsqty + @purchaselsqty + @Prodlsqty)) % @kit


	select @fulqty=sum(fulqty),@loosqty=sum(loosqty) from stockledger  where itemcode=@itemcode "; 
        $this->procedure .=($this->StoreCode=='AllStores')?" and date <= '".$this->Todate."' ":  " and date <='".$this->Todate."' and store= '".$this->StoreCode."'";
	
        $this->procedure .="select @Closingfqty = IFNULL(@fulqty,0) + CAST(IFNULL(@loosqty,0)/@kit AS int)
	Select @Closinglsqty =((@Closingfqty * @kit) + IFNULL(@loosqty,0)) % @kit
         	
	insert into #inventory ([itemcode],[stockname],[averagestock],[partperunit],[fulqtyName],[LoosqtyName],[openstock_f],[openstock_l],[Purchases_f],[Purchases_l],[Prod_f],[Prod_l],[Total_f],[total_l],[Sales_f],[Sales_l],[Work_f],[Work_l],[Close_f],[Close_l])
	values (@itemcode,@stockname,@ave,@kit,@fulldesc,@loosedesc,@Openfqty,@openlsqty,@Purchasesfqty,@purchaselsqty, @Prodfqty,@Prodlsqty,@Totalfqty,@Totallsqty,@Salesfqty,@Saleslsqty,@Worderqty,@Worklsqty,@Closingfqty,@Closinglsqty)

	FETCH NEXT FROM  Sstock into @itemcode,@stockname,@ave,@kit,@fulldesc,@loosedesc
	END
	CLOSE  Sstock
	DEALLOCATE  Sstock

	select itemcode,stockname,averagestock,partperunit,fulqtyName,LoosqtyName,openstock_f,openstock_l,Purchases_f,Purchases_l,[Prod_f],[Prod_l],Total_f,total_l, Sales_f,Sales_l, [Work_f], [Work_l],Close_f,Close_l from  #inventory

       END";
        
      return  $this->procedure;
      }
  
      
      function dropprocedure($procedurename){
        global $db;

          $SQL="IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[".$procedurename."]') AND type in (N'P', N'PC'))
                DROP PROCEDURE [".$procedurename."]";
         DB_query($SQL,$db);

        }
      
      
      function EXECUTE(){
          Global $db;
          
          $this->dropprocedure('InventoryReport');
          $reslts=$this->CreateScript();
          $Results=DB_query($reslts,$db);
          $Results=DB_query('[InventoryReport]',$db);
          
          Return  $Results;
      }
      
      
  }
  
    
?>