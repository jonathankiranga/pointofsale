<?php
$Errors=0;
DB_query("[update_tank_balance]",$db);

$Home_Currency = $_SESSION['CompanyRecord']['currencydefault'];
  
if(isset($_POST['submit']) and $_POST['submit']=='Run Production' ){
    
    foreach ($_POST['stockbalance'] as $rowid => $tankvalue) {
        if($tankvalue<0){
            $Errors++;
        }
    }
    
    if($Errors>0){
        prnMsg('There are tank balances that are below zero. Please issue stock from <a href="WorkorderRequisation.php" target="_blank">Click Here</a> then try again','warn');
    }
       
    if($_POST['store_BOM'][$_POST['BOM']]=='tank'){
        if(tankcapacity($_POST['BOM'])< ($_POST['production'])){
            prnMsg('You have produced more than your storage capacity. Please enter the correct figure <a href="Tanksunit.php" target="_blank">or change the tank capacity here</a> ','warn');
            $Errors++;
        }
     }  
     
    $sql=array();
    if($Errors==0){
        
         $journal= GetNextTransNo(0,$db);
         $period = GetPeriod($_POST['Date'],$db,false);
         
         $sql[] = sprintf("INSERT INTO [ProductionMaster] ([Batchno],[date],[userid]) VALUES ('%s','%s','%s')",
                 $_POST['Batchno'],FormatDateForSQL($_POST['Date']),stripslashes($_SESSION['UserID']));
                 
         
         $sql[]= sprintf("INSERT INTO [ProdcutionMasterLine] ([Batchno],[itemcode],[UOM],[qty],[cost])  "
                 . "VALUES ('%s','%s','%s','%s',%f) ",
                 $_POST['Batchno'] ,$_POST['BOM'],$_POST['FUOM'][$_POST['BOM']],
                 $_POST['production'],$_POST['averagecost']);
        
        
         if($_POST['store_BOM'][$_POST['BOM']]=='tank'){

            $sql[] = sprintf("INSERT INTO [tanktrans] ([tankname],[units],[uom],[date],[batchno],[doctype],[itemcode])
                     VALUES  ('%s',%f ,'%s' ,'%s' ,'%s',28,'%s')",
                                 $_POST['productiontank'][$_POST['BOM']],
                                 $_POST['production'],
                                 $_POST['FUOM'][$_POST['BOM']],
                                 FormatDateForSQL($_POST['Date']),
                                 $_POST['Batchno'],$_POST['BOM']);

            } else {
                $uom='loosqty';

                $sql[] = "INSERT [stockledger] ([date],[stname],[doctyp],[itemcode] ,[invref],[netamt],[vat],[amount]
                ,[fulqty],[loosqty],[price],[store],[journal],[period]) values ('".FormatDateForSQL($_POST['Date'])."' ,'".$_POST['location'][$_POST['BOM']]."','28','".$_POST['BOM']."','".$_POST['Batchno']."'
                ,0,0,0,0,".($_POST['production']).",0,'".$_POST['location'][$_POST['BOM']]."','". $journal ."','". $period ."')";
           }
         
        
   if($_POST['averagecost']>0){ 
        
           $sql[] = "Update [stockmaster] set [averagestock]=".$_POST['averagecost']." where itemcode='".$_POST['BOM']."'";
           
           $sql[] = " INSERT INTO [StockRegister] ([itemcode],[StockIn],[cost],[StockOut],[journal],[GRN]) 
            VALUES  ('".$_POST['BOM']."', ".$_POST['production']." ,".$_POST['averagecost'].", 0 , '".$journal."' ,'".$_POST['Batchno']."')";
    }
          
    foreach ($_POST['requirement'] as $rowid => $tankvalue) {

    if($_POST['store_tank'][$rowid]=='tank'){

         $sql[] = sprintf("INSERT INTO [tanktrans] ([tankname],[units],[uom],[date],[batchno],[doctype],[itemcode])
          VALUES  ('%s',%f ,'%s' ,'%s' ,'%s',28,'%s')", $_POST['productiontank'][$rowid], ($tankvalue * -1), $_POST['RUOM'][$rowid], FormatDateForSQL($_POST['Date']),  $_POST['Batchno'], $rowid);

    } elseif(IfserviceType($rowid)=='0') {

        $uom = $_POST['RUOM'][$rowid];

        $sql[] = "INSERT [stockledger] ([date],[stname],[doctyp],[itemcode],[invref],[netamt],[vat],[amount],
        [fulqty],[loosqty],[price],[store],[journal],[period]) values ('".FormatDateForSQL($_POST['Date'])."',
        '".$_POST['location'][$rowid]."','28','".$rowid."','".$_POST['Batchno']."',0,0,0,".
        ($uom=='fulqty'?($tankvalue * -1):0).",".($uom=='loosqty'?($tankvalue * -1):0)
       .",0,'".$_POST['location'][$rowid]."','". $journal ."' ,'". $period ."')";
        
    }

    $sql[] = sprintf("INSERT INTO [ProdcutionMasterLine] ([Batchno],[itemcode],[UOM],[qty],[cost]) "
     . " VALUES ('%s','%s','%s','%s',%f) ",$_POST['Batchno'],$rowid,$_POST['RUOM'][$rowid],$tankvalue,$_POST["AVE"][$rowid]);
   
    
    }
        
        DB_Txn_Begin($db);
        foreach ($sql as $value) {
            DB_query($value,$db);
        }
        
        if(DB_error_no($db)>0){
            DB_Txn_Rollback($db);
        }else{
            DB_Txn_Commit($db);
        
            DB_query("[update_tank_balance]",$db);
            
            prnMsg('This batch No is '.$_POST['Batchno'] ,'sucess');
        
        }
        unset($_POST);
    }
    
}

function CreateUnits($stockcode,$pkey){
       Global $db;

       $line="";


  $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
  FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code 
  left join [unit] l on [stockmaster].[subunits]=l.code 
  where itemcode= '".$stockcode."'", $db);

       $line .='<select name="CUOM['.$pkey.']" required="required">';

       $rows=  DB_fetch_row($REsults);
           $line .=sprintf('<option value="fulqty" %s >'.$rows[0].'</option>'
                   . '<option value="loosqty" %s >'.$rows[1].'</option>'
                   ,$_POST['UOM'][$pkey]=='fulqty'?'selected="selected"':'',
                   $_POST['UOM'][$pkey]=='loosqty'?'selected="selected"':'');

       $line .= '</select>';


        return $line;
   }

function Getstocknames($select='empty'){
    global $db;
    $option='';

    $sql="Select itemcode,descrip from stockmaster where (isstock=1 or isstock=4 or isstock=5) "
            . " and itemcode not in (select parent_itemcode from [billofmaterial]) "
            . " order by descrip";
    $ResultIndex=DB_query($sql,$db);
    while($row=DB_fetch_array($ResultIndex)){
        $option .= sprintf('<option value="%s" %s>%s</option>',$row['itemcode'],
                (($select==$row['itemcode'])?'selected="selected"':""), trim($row['descrip']));
    }
      return $option;
}

function GetConumerstocknames($select='empty'){
    global $db;
    $option='';

    $sql="Select itemcode,descrip from stockmaster where isstock=2 order by descrip";
    $ResultIndex=DB_query($sql,$db);
    while($row=DB_fetch_array($ResultIndex)){
          $option .= sprintf('<option value="%s" %s>%s</option>',$row['itemcode'],
          (($select==$row['itemcode'])?'selected="selected"':""), trim($row['descrip']));
    }

    return $option;
}

function GetUnits($stockcode){
    
 Global $db;
 $list=array();

  $sql="SELECT f.descrip as fulqty, l.descrip as loosqty
  FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code 
  left join [unit] l on [stockmaster].[subunits]=l.code 
  where itemcode= '".$stockcode."'";

  $REsults=DB_query($sql,$db);
       $rows=DB_fetch_row($REsults);
       $list['fulqty']=$rows[0];
       $list['loosqty']=$rows[1];
       $uom=isset($_POST['UOM'])?$_POST['UOM']:'empty';
      return '<option value="fulqty" '.(($uom=='fulqty')?'selected="selected"':'').'>'.$list['fulqty'].'</option>'
           . '<option value="loosqty" '.(($uom=='loosqty')?'selected="selected"':'').'>'.$list['loosqty'].'</option>';

   }

function selectUOM($stockcode,$OUM){
  Global $db;

  $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
  FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code 
  left join [unit] l on [stockmaster].[subunits]=l.code 
  where itemcode= '".$stockcode."'", $db);
  $rows=DB_fetch_row($REsults);

  $trimOUM = trim($OUM);
  
  return ($trimOUM=='fulqty')?$rows[0]:$rows[1];
}   

function Findstockdetails($stockcode){
    global $db;

    $ResultIndex = DB_query("SELECT 
        [stockmaster].[itemcode],
        [stockmaster].[descrip] as [stockname],
        [unit].[descrip] as [si],
        [stockmaster].[averagestock],
        [stockmaster].[partperunit],
        [vatcategory].[vat]
  FROM [stockmaster] 
  left join [unit] on [stockmaster].[units]=[unit].[code]
  left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
  left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
  where [stockmaster].inactive=0 and ([stockmaster].[isstock]=1 
     or [stockmaster].[isstock]=4 or [stockmaster].[isstock]=5) and itemcode='".$stockcode."'
  order by [stockmaster].[descrip]", $db);

   $rows = DB_fetch_row($ResultIndex);

   return $rows[1];
}   

function tankbalance($tank){
    global $db;
    $sql="select IFNULL([units],0) from [ProductionUnit] where tankname='".$tank."'";
     
    $ResultIndex=DB_query($sql,$db);
    $value =DB_fetch_row($ResultIndex);
    
    return $value[0];
}

function tank_itemcode_balance($tank){
    global $db;
    $sql="select IFNULL([units],0) from [ProductionUnit] where tankname='".$tank."'";
    $ResultIndex=DB_query($sql,$db);
    $value =DB_fetch_row($ResultIndex);
    
    return $value[0];
}

function Checktank($tank){
    global $db,$allhavetanks;
    
    $ResultIndex=DB_query("select count(*) as 'T' from [ProductionUnit] where itemcode='".$tank."'", $db);
    $value=DB_fetch_row($ResultIndex);
  
    if($value[0]==0){
        $allhavetanks=false;
    }
    
    return $value[0];
}

function Checkrowtank($tank){
    global $db,$allhavetanks;
    
    $ResultIndex=DB_query("select count(*) as 'T' from [ProductionUnit] where itemcode='".$tank."'", $db);
    $value=DB_fetch_row($ResultIndex);
    
    return $value[0];
}

Function GetcmbTank($itemcode){
    global $db;
    
    $ResultIndex=DB_query("select tankname from [ProductionUnit] where itemcode='".$itemcode."'", $db);
    while($value=DB_fetch_array($ResultIndex)){
        if($_POST['productiontank'][$itemcode]==trim($value['tankname'])){
            echo '<option value="'.trim($value['tankname']).'"  selected="selected">'.$value['tankname'].'</option>';
        }else{
           echo '<option value="'.trim($value['tankname']).'" >'.$value['tankname'].'</option>';
       
        }
        
    }
}

function tankcapacity($itemcode){
    global $db;
    
    $ResultIndex=DB_query("select sum([balance]) from [ProductionUnit] where itemcode='".$itemcode."'", $db);
    $value =DB_fetch_row($ResultIndex);
    
    return $value[0];
}

function createstores($code){
       global $db;
       $line="";
      
       $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
       $line .='<td><select name="location['.$code.']" required="required">';
              
       while($rows=  DB_fetch_array($REsults)){
           $line .= sprintf('<option value="%s" %s >%s</option>',
                   $rows['code'],$_POST['location'][$code]==$rows['code']?'selected="selected"':'',$rows['Storename']);
       }
       $line .= '</select></td>';
       
       
        return $line;
    }
    
function getbalance($stcode,$location,$UOM=''){
        global $db;
        
       $sql="SELECT  
            sum(fulqty) as funits , 
            sum(loosqty) as lunits ,
            stockmaster.partperunit 
            from stockledger
            join stockmaster on stockledger.itemcode=stockmaster.itemcode 
            where stockledger.itemcode='".$stcode."' "
                . " and stockledger.store='".$location."' "
                . "group by partperunit";        
        
       $ResultIndex=DB_query($sql,$db);
       $rows = DB_fetch_row($ResultIndex);
       
       $TrimUOM=trim($UOM);
       
       if($TrimUOM=='fulqty'){
            $ans = $rows[0];
       }else{
            $ans = ($rows[0] * $rows[2]) + $rows[1];
       }
      
       return $ans;
    }
    
function Checkstocktype($itemcode){
        global $db;
        
       $sql="SELECT  stockmaster.[isstock] from stockmaster where itemcode='".$itemcode."' ";        
       $ResultIndex=DB_query($sql,$db);
       $rows = DB_fetch_row($ResultIndex);
     
       return $rows[0];
    }
    
function IfserviceType($itemcode){
        global $db;
        
       $sql="SELECT stockmaster.[isstock] from stockmaster where itemcode='".$itemcode."' ";        
       $ResultIndex=DB_query($sql,$db);
       $rows = DB_fetch_row($ResultIndex);
     
       return (($rows[0]==3)?1:0);
    }

?>