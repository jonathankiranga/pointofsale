<?php

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
    
   function getbalance($stcode,$location,$UOM=''){
        global $db;

        $itemcode = str_replace("'", "''", $stcode);
        $store = str_replace("'", "''", $location);
        $sql = "SELECT
                    COALESCE(SUM(COALESCE(stockledger.fulqty,0) * COALESCE(stockmaster.partperunit,0)),0)
                    + COALESCE(SUM(COALESCE(stockledger.loosqty,0)),0) AS baseunits,
                    COALESCE(stockmaster.partperunit,0) AS partperunit
                FROM stockmaster
                LEFT JOIN stockledger
                    ON stockledger.itemcode = stockmaster.itemcode
                    AND stockledger.store = '".$store."'
                WHERE stockmaster.itemcode = '".$itemcode."'
                GROUP BY stockmaster.partperunit";

        $result = DB_query($sql,$db);
        if(DB_num_rows($result) < 1){
            return 0.0;
        }

        $row = DB_fetch_row($result);
        $baseUnits = (float)$row[0];
        $partPerUnit = (float)$row[1];

        if($UOM === 'fulqty'){
            return ($partPerUnit > 0) ? (float)((int)($baseUnits / $partPerUnit)) : 0.0;
        }
        return $baseUnits;
    }
    
   function CreateUnits($stockcode,$pkey){
       Global $db;
       
       $line="";
       
       
  $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
  FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code 
  left join [unit] l on [stockmaster].[subunits]=l.code 
  where itemcode= '".$stockcode."'", $db);
       
       
       $line .='<select name="UOM['.$pkey.']" required="required">';
              
       $rows=  DB_fetch_row($REsults);
           $line .=sprintf('<option value="fulqty" %s >'.$rows[0].'</option>'
                   . '<option value="loosqty" %s >'.$rows[1].'</option>'
                   ,$_POST['UOM'][$pkey]=='fulqty'?'selected="selected"':'',
                   $_POST['UOM'][$pkey]=='loosqty'?'selected="selected"':'');
       
       
       
       $line .= '</select>';
       
       
        return $line;
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
   
   function createlocations($code){
       global $db;
       $line="";
       
       
       $REsults=DB_query('SELECT [locationid],[locationdescription] FROM [fixedassetlocations]', $db);
       $line .='<select name="location['.$code.']" required="required">';
              
       while($rows=  DB_fetch_array($REsults)){
           $line .= sprintf('<option value="%s" %s >%s</option>',
           $rows['locationid'],$_POST['location'][$code]==$rows['locationid']?'selected="selected"':'',$rows['locationdescription']);
       }
       $line .= '</select>';
       
       
        return $line;
   }
?>