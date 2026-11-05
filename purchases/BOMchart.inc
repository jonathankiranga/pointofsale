<?php

    class BOM {
     var  $itemcodestock='';  
     var  $htmltable='';
     var  $stocklist=array();
     
     function GetStockdetails(){
         global $db;
        
         $SQL="SELECT 
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
        where [stockmaster].itemcode='".$this->itemcodestock."'";
          
         $ResultIndex = DB_query($SQL,$db);
         $stocklist = DB_fetch_row($ResultIndex);
         $this->stocklist['itemcode'] = TRIM($stocklist[0]);
         $this->stocklist['stockname'] = trim($stocklist[1]);
         $this->stocklist['si'] = TRIM($stocklist[2]);
         $this->stocklist['averagestock'] = $stocklist[3];
         $this->stocklist['partperunit'] = $stocklist[4];
         $this->stocklist['vat'] = $stocklist[5];
         
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty){
         
         $this->itemcodestock=trim($itemcode);
         
         $StockList = $this->GetStockdetails();
         $UOM = trim($_POST['CUOM'][$this->itemcodestock]);
         
         $_SESSION['BOM_orders'][$this->LineNo()]=
            array('itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>$UOM,
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$qty);
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['BOM_orders_index'];
     }
     
     function neworder(){
         $_SESSION['BOM_orders']=array();
         $_SESSION['BOM_orders_index']=0;
     }
     
     function Nextorder(){
         $_SESSION['BOM_orders_index']++;
    }
    
     function showtable(){
        global $db;
        $this->htmltable='';
        
        foreach ($_SESSION['BOM_orders'] as $lineno => $rowvalue) {
             $itemcode = $rowvalue['itemcode'];

             if(isset($_POST['quantity'][$itemcode])){
                 $qty = $_POST['quantity'][$itemcode];
             }else{
                 $qty = $rowvalue['quantity'];
             }
             
         
             $this->htmltable .= sprintf('<tr>'
             . '<td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>'
             . '<td><input type="integer" class="number" name="quantity['.$itemcode.']" value="'.$qty.'" size="5" maxlength="10"/></td>'
             . '</tr>',$rowvalue['itemcode'],
                       $rowvalue['stockname'], 
                       CreatelooseUnits($itemcode,$itemcode), 
                       $rowvalue['averagestock'],
                       $rowvalue['partperunit']);

         }

        echo $this->htmltable;
          
    }
  
     function Confirmitems($ROW=array()){
         $this->itemcodestock=$ROW['itemcode'];
         $StockList = $this->GetStockdetails();
         
         $_SESSION['BOM_orders'][$this->LineNo()] = array(
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>trim($ROW['uom']),
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>round($StockList['averagestock'],2),
                  'quantity'=>$ROW['qty']);
         
         $this->Editshowtable();
         
     }
    
     function Editshowtable(){
         $this->htmltable ='';
    
         $rowvalue  = $_SESSION['BOM_orders'][$this->LineNo()];
         $itemcode  = $rowvalue['itemcode'];
         
         if(isset($_POST['quantity'][$itemcode])){
             $qty = $_POST['quantity'][$itemcode];
         } else {
             $qty = $rowvalue['quantity'];
         }
         
         $this->htmltable = sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>'
         . '<td><input type="integer" class="number" name="quantity['.$itemcode.']" value="'.$qty.'" size="5" maxlength="10"/></td>'
         . '</tr>',$rowvalue['itemcode'],
                   $rowvalue['stockname'], 
                   CreatelooseUnits($itemcode,$itemcode), 
                   $rowvalue['averagestock'],
                   $rowvalue['partperunit']);

         echo $this->htmltable;
        
    }
  
    
     function Autoshowtable(){
        $this->htmltable ='';
        foreach ($_SESSION['BOM_orders'] as $lineno => $rowvalue) {
             $itemcode = $rowvalue['itemcode'];

             if(isset($_POST['quantity'][$itemcode])){
               $qty = $_POST['quantity'][$itemcode];
             }else{
               $qty = $rowvalue['quantity'];
             }
          
          
             $this->htmltable .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>'
             . '<td><input type="integer" class="number" name="quantity['.$itemcode.']" value="'.$qty.'" size="5" maxlength="10"/></td>'
             . '</tr>',$rowvalue['itemcode'],
                       $rowvalue['stockname'], 
                       CreatelooseUnits($itemcode,$itemcode), 
                       $rowvalue['averagestock'],
                       $rowvalue['partperunit']);

         }

        echo $this->htmltable;
        
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

           $rows = DB_fetch_row($REsults);
               $line .= sprintf('<option value="fulqty" %s >'.$rows[0].'</option>'
                       . '<option value="loosqty" %s >'.$rows[1].'</option>'
                       ,$_POST['CUOM'][$pkey]=='fulqty'?'selected="selected"':'',
                       $_POST['CUOM'][$pkey]=='loosqty'?'selected="selected"':'');

           $line .= '</select>';

        return $line;
    }
       
    function CreatelooseUnits($stockcode,$pkey){
           Global $db;
            $line="";
          
            $sql="SELECT f.descrip as fulqty, l.descrip as loosqty
            FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code 
            left join [unit] l on [stockmaster].[subunits]=l.code 
            where itemcode= '".$stockcode."'";
           $REsults = DB_query($sql,$db);
           $uom  = $_POST['CUOM'][$pkey];
           $rows = DB_fetch_row($REsults);
           
           $line .='<select name="CUOM['.$pkey.']" required="required">';
           $line .='<option value="fulqty" '.(($uom=='fulqty')?'selected="selected"':'').'>'.$rows[0].'</option>';
           $line .='<option value="loosqty" '.(($uom=='loosqty')?'selected="selected"':'').'>'.$rows[1].'</option>';
           $line .= '</select>';
           
            return $line;
    }   

    function Getstocknames($select='empty'){
        global $db;
        $option='';

        $sql="Select "
           . " itemcode,descrip from stockmaster where isstock='4' "
           . " and itemcode not in (select [parent_itemcode] from [billofmaterial])"
           . " order by descrip";
        $ResultIndex=DB_query($sql,$db);
        while($row=DB_fetch_array($ResultIndex)){
            $option .= sprintf('<option value="%s" %s>%s</option>',$row['itemcode'],
                    (($select==$row['itemcode'])?'selected="selected"':""), trim($row['descrip']));
        }
          return $option;
    }

    
    function Selectstocknames($select='empty'){
        global $db;
        $option='';

        $sql="Select "
           . " itemcode,descrip from stockmaster order by descrip";
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
         
           $uom=isset($_POST['UOM'])?$_POST['UOM']:'';
          
     echo '<option value="fulqty" '.(($uom=='fulqty')?'selected="selected"':'').'>'.$list['fulqty'].'</option>'
        . '<option value="loosqty" '.(($uom=='loosqty')?'selected="selected"':'').'>'.$list['loosqty'].'</option>';

    }
              
    function GetUnitsT($stockcode){
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
           $uom=isset($_POST['UOMT'])?$_POST['UOMT']:'empty';

           return '<option value="fulqty" '.(($uom=='fulqty')?'selected="selected"':'').'>'.$list['fulqty'].'</option>'
                . '<option value="loosqty" '.(($uom=='loosqty')?'selected="selected"':'').'>'.$list['loosqty'].'</option>';

       }
       
    function GetUnitsF($stockcode){
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
           $uom=isset($_POST['UOMF'])?$_POST['UOMF']:'empty';

           return '<option value="fulqty" '.(($uom=='fulqty')?'selected="selected"':'').'>'.$list['fulqty'].'</option>'
                . '<option value="loosqty" '.(($uom=='loosqty')?'selected="selected"':'').'>'.$list['loosqty'].'</option>';

       }
        
    function GetlooseUnits($stockcode){
           Global $db;
           $list=array();

      $sql="SELECT  l.descrip as loosqty
      FROM [stockmaster] 
      left join [unit] l on [stockmaster].[subunits]=l.code 
      where itemcode= '".$stockcode."'";

      $REsults=DB_query($sql,$db);
           $rows=DB_fetch_row($REsults);
           $list['loosqty']=$rows[0];
           return  '<option value="loosqty" selected="selected">'.$list['loosqty'].'</option>';

       }   

    function selectUOM($stockcode,$OUM){
      Global $db;

      $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
      FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code 
      left join [unit] l on [stockmaster].[subunits]=l.code 
      where itemcode= '".$stockcode."'", $db);
      $rows=DB_fetch_row($REsults);
      
      return ($OUM=='fulqty')?$rows[0]:$rows[1];
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
          where  itemcode='".$stockcode."'
          order by [stockmaster].[descrip]", $db);

           $rows = DB_fetch_row($ResultIndex);

           return $rows[1];
        }   
        
    function Getanystockname($select='empty'){
        global $db;
        $option='';

        $sql="Select itemcode,descrip from stockmaster where (isstock=1 or isstock=4 or isstock=5) "
                . " order by descrip";
        $ResultIndex=DB_query($sql,$db);
        while($row=DB_fetch_array($ResultIndex)){
            $option .= sprintf('<option value="%s" %s>%s</option>',$row['itemcode'],
                    (($select==$row['itemcode'])?'selected="selected"':""), trim($row['descrip']));
        }
          return $option;
    }   
    
    function StockBalance($stcode,$location,$UOMF){
        global $db;
        
        if($UOMF='loosqty'){
        $sql="SELECT  
            sum(fulqty * stockmaster.partperunit) as funits , 
            sum(loosqty) as lunits  
            from stockledger join stockmaster on stockledger.itemcode=stockmaster.itemcode 
            where stockledger.itemcode='".$stcode."' "
                . "and stockledger.store='".$location."' ";
        }else{
            $sql="SELECT  
            sum(fulqty) as funits , 
            sum(loosqty/stockmaster.partperunit) as lunits  
            from stockledger join stockmaster on stockledger.itemcode=stockmaster.itemcode 
            where stockledger.itemcode='".$stcode."' "
                . "and stockledger.store='".$location."' ";
        }
        
        
           $ResultIndex=DB_query($sql,$db);
           $rows = DB_fetch_row($ResultIndex);
          
          return $rows[0]+$rows[1];
    }
        
    function selectUOMone($stockcode,$OUM){
      Global $db;
      $oum=trim($OUM);

      $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty
      FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code 
      left join [unit] l on [stockmaster].[subunits]=l.code 
      where itemcode= '".$stockcode."'", $db);
      $rows=DB_fetch_row($REsults);
      
      return (($oum=='fulqty') ? '<option value="fulqty">'.$rows[0].'</option>' : '<option value="loosqty">'.$rows[1].'</option>');
    }   
    
    function createstores($POST_NAME){
       global $db;
       $line="";
              
       $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
        while($rows=  DB_fetch_array($REsults)){
           $line .= sprintf('<option value="%s" %s >%s</option>',
                   $rows['code'],($POST_NAME==$rows['code']?'selected="selected"':''),$rows['Storename']);
       }
              
        return $line;
    }
    

?>