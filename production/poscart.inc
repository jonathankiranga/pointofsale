<?php

 class Production {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
    
     function GetStockdetails($itemcode){
         global $db;
         
         $ResultIndex = DB_query("SELECT 
                        [stockmaster].[barcode],
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
                  where [stockmaster].itemcode='".$itemcode."'", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode']= $stocklist[0];
         $this->stocklist['itemcode']= $stocklist[1];
         $this->stocklist['stockname']= $stocklist[2];
         $this->stocklist['si']= $stocklist[3];
         $this->stocklist['averagestock']= $stocklist[4];
         $this->stocklist['partperunit']= $stocklist[5];
         $this->stocklist['vat']= $stocklist[6];
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty){
         $StockList = $this->GetStockdetails($itemcode);
         $line_No   = $this->LineNo();
          
         $_SESSION['labreagents'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'partperunit'=>$StockList['partperunit'],
                  'quantity'=>$qty
                  );
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['labreagents_index'];
     }
     
     function neworder(){
         $_SESSION['labreagents'] = array();
         $_SESSION['labreagents_index'] = 0;
     }
     
     function Nextorder(){
         $_SESSION['labreagents_index']++;
    }
    
    function showtable(){
        global $db;
               
        foreach ($_SESSION['labreagents'] as $lineno => $rowvalue) {
                $line_no   = $rowvalue['line_no'];
                $itemcode  = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                
                if(isset($_POST['quantity'][$line_no])){
                    $qty = $_POST['quantity'][$line_no];
                }else{
                    $qty = 0;
                }
                 
                $this->htmltable .= sprintf('<tr>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="text" class="number" name="quantity['.$line_no.']" value="'.$qty.'" size="5"/>'
              . '<input type="hidden"  name="itemcode['.$line_no.']" value="'.$stocklist['itemcode'].'"/></td>'
              . '<td>%s</td>'
              . '</tr>',trim($stocklist['barcode']), 
                        trim($stocklist['stockname']),
                        CreateUnits($itemcode,$line_no),
                        createstores($line_no) );
            }
        echo $this->htmltable;

    }
       
 }
 
?>