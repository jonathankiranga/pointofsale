<?php

Function GetProductOption(){
    global $db;
    
    $ResultIndex=DB_query("select itemcode,descrip FROM [stockmaster] "
    . " where inactive=0 "
    . "and (isstock=1 or isstock=2 or isstock=4 or isstock=5) Order by descrip", $db);
    while($value=DB_fetch_array($ResultIndex)){
        if($_POST['Prod_itemcode']==trim($value['itemcode'])){
            echo '<option value="'.trim($value['itemcode']).'"  selected="selected">'. trim($value['descrip']).'</option>';
        }else{
           echo '<option value="'.trim($value['itemcode']).'" >'.trim($value['descrip']).'</option>';
       
        }
        
    }
}   
     
function createstores($code){
       global $db;
       $line="";
       
       
       $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
       $line .='<select name="location['.$code.']" required="required">';
              
       while($rows=  DB_fetch_array($REsults)){
           $line .= sprintf('<option value="%s" %s >%s</option>',
                   $rows['code'],$_POST['location'][$code]==$rows['code']?'selected="selected"':'',$rows['Storename']);
       }
       $line .= '</select>';
       
       
        return $line;
    }
    
function CreateUnits($stockcode,$pkey){
       Global $db;
       
       $line="";
       
       
  $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
  FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code 
  left join [unit] l on [stockmaster].[subunits]=l.code where itemcode= '".$stockcode."'", $db);
       
       
       $line .='<select name="UOM['.$pkey.']" required="required" onchange="CalculateForm(salesform.submit)">';
              
       $rows=  DB_fetch_row($REsults);
        $line .=sprintf('<option value="fulqty" %s >'.$rows[0].'</option>'
                   . '<option value="loosqty" %s >'.$rows[1].'</option>'
                   ,$_POST['UOM'][$pkey]=='fulqty'?'selected="selected"':'',
                   $_POST['UOM'][$pkey]=='loosqty'?'selected="selected"':'');
       
       
       
       $line .= '</select>';
       
       
        return $line;
   }
    
   function getbalance($stcode,$location,$UOM=''){
        global $db;
        
       $sql="SELECT  
            cast(((IFNULL(sum(fulqty),0) * partperunit) + IFNULL(sum(loosqty),0))/partperunit as int) as fulqty,
            ((IFNULL(sum(fulqty),0) * partperunit) + IFNULL(sum(loosqty),0)) % partperunit as loosqty,
            stockmaster.partperunit  from stockledger join stockmaster on stockmaster.itemcode=stockledger.itemcode
            where stockledger.itemcode='".$stcode."' "
                . " and stockledger.store='".$location."' "
                . " group by partperunit";        
       
       $ResultIndex=DB_query($sql,$db);
       $rows = DB_fetch_row($ResultIndex);
       if($UOM=='fulqty'){
            $ans = $rows[0] ;
       }else{
            $ans = ($rows[0] * $rows[2] )+$rows[1];
       }
      
       return number_format($ans,0);
    }
    
   function GetUnits($stockcode){
       Global $db;
       $list=array();
       
  $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
  FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code 
  left join [unit] l on [stockmaster].[subunits]=l.code where itemcode= '".$stockcode."'", $db);
       
       $rows= DB_fetch_row($REsults);
       $list['fulqty']=$rows[0];
       $list['loosqty']=$rows[1];
       
       return $list;
   }
     
   Function GetwoTank(){
    global $db;
    
    $ResultIndex=DB_query("select tankname, stockmaster.descrip from [ProductionUnit] "
            . "join stockmaster on ProductionUnit.itemcode=stockmaster.itemcode  ", $db);
    while($value=DB_fetch_array($ResultIndex)){
        if($_POST['wotank']==trim($value['tankname'])){
            echo '<option value="'.trim($value['tankname']).'"  selected="selected">'.$value['tankname'].'-'. $value['descrip'].'</option>';
        }else{
           echo '<option value="'.trim($value['tankname']).'" >'.$value['tankname'].'-'. $value['descrip'].'</option>';
       
        }
        
    }
}

   function CreatepageUnits($stockcode,$pkey){
       Global $db;
       
       $line="";
       
       
  $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
  FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code 
  left join [unit] l on [stockmaster].[subunits]=l.code where itemcode= '".$stockcode."'", $db);
       
       
       $line .='<select name="UOM['.$pkey.']" required="required" onchange="ReloadForm(salesform.submit)">';
              
       $rows=  DB_fetch_row($REsults);
        $line .=sprintf('<option value="fulqty" %s >'.$rows[0].'</option>'
                      . '<option value="loosqty" %s >'.$rows[1].'</option>'
                   ,$_POST['UOM'][$pkey]=='fulqty'?'selected="selected"':'',
                   $_POST['UOM'][$pkey]=='loosqty'?'selected="selected"':'');
       
       
       
       $line .= '</select>';
       
       
        return $line;
   }
    
?>