<?php

 class Pos {
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
         
         $stocklist = DB_fetch_row($ResultIndex);
         $this->stocklist['barcode'] = $stocklist[0];
         $this->stocklist['itemcode'] = $stocklist[1];
         $this->stocklist['stockname'] = $stocklist[2];
         $this->stocklist['si'] = $stocklist[3];
         $this->stocklist['averagestock'] = $stocklist[4];
         $this->stocklist['partperunit'] = $stocklist[5];
         $this->stocklist['vat'] = $stocklist[6];
         
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty,$price){
         $StockList = $this->GetStockdetails($itemcode);
         $UOM = $_POST['UOM']['$itemcode'];
         $line_No = $this->LineNo();
         
         $_SESSION['Purchase_orders'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>$UOM,
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$qty,
                  'salesprice'=>$price,
                  'netamount'=>$net,
                  'vatamount'=>$vat,
                  'grossamount'=>$gross);
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['Purchase_orders_index'];
     }
     
     function neworder(){
         $_SESSION['Purchase_orders']=array();
         $_SESSION['Purchase_orders_index']=0;
         
     }
     
     function Nextorder(){
         $_SESSION['Purchase_orders_index']++;
    }
    
     function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;

        if(!isset($_POST['FREIGHT'])){
            $_POST['FREIGHT']=0;
        }
            $results=DB_query("SELECT 
                [itemcode] ,[customer], [phone] ,
                [email] ,[city] ,[country],[curr_cod],
                [supplierposting],[VATinclusive],[IsTaxed]
            FROM [creditors] join arpostinggroups on code=[supplierposting] 
            where itemcode='".$_POST['VendorID']."'", $db);
              
            $debtorsrow = DB_fetch_row($results);
            $this->customerposting = $debtorsrow[7];
            $this->VATinclusive = $debtorsrow[8];
            $this->IsTaxed = $debtorsrow[9];

            
            foreach ($_SESSION['Purchase_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0;
                $cnetamount=0; $cgrossamount=0; $emptyunits=0;
                
                $line_no = $rowvalue['line_no'];
                $itemcode = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $rate = (($this->IsTaxed==false)?0:$stocklist['vat']);
    
                if(isset($_POST['quantity'][$line_no])){
                    $qty= $_POST['quantity'][$line_no];
                }else{
                    $qty=.0;
                }
        
                if(isset($_POST['salesprice'][$line_no])){
                    $salesprice= $_POST['salesprice'][$line_no] ;
                }else{
                    $salesprice=.0;
                }
         
                
                $baseamount = ($salesprice * $qty);
               
                
            if($this->VATinclusive == true){
                $netamount   = $baseamount /  round((($rate+100)/100),2);
                $vatamount   = $baseamount - $netamount;
                $grossamount = $baseamount ;
            }else{
                $vatamount  = $baseamount * ($rate/100);
                $grossamount= $baseamount + $vatamount;
                $netamount  = $baseamount;
            }
    
                $runningnettotal += $netamount;
                $runningvattotal += $vatamount;
                $runninggrosstotal += $grossamount;
                
                $this->htmltable .= sprintf('<tr>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td class="number"><input type="text" class="integer" name="quantity['.$line_no.']" value="'.$qty.'" size="5"/>'
              . '<input type="hidden"  name="itemcode['.$line_no.']" value="'.$stocklist['itemcode'].'"/></td>'
              . '<td class="number"><input type="text" class="number" name="salesprice['.$line_no.']" value="'.$salesprice.'" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="netamount['.$line_no.']" value="'.$netamount.'" readonly="readonly" size="10"/></td>'
              . '<td class="number"><input type="text" class="number" name="vatamount['.$line_no.']" value="'.$vatamount.'" readonly="readonly" size="10"/></td>'
              . '<td class="number"><input type="text" class="number" name="grossamount['.$line_no.']" value="'.$grossamount.'" readonly="readonly" size="10"/></td>'
            . '</tr>',trim($stocklist['itemcode']),
                      trim($stocklist['stockname']),
                      CreateUnits($itemcode,$line_no),
                      number_format($stocklist['partperunit'],0));
            }
            
                       
            $this->htmltable .= sprintf('<tr>'
              . '<td colspan="6">TOTAL LPO VALUE</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr>', 
                      number_format($runningnettotal,2),
                      number_format($runningvattotal,2),
                      number_format($runninggrosstotal,2));
            
            $_SESSION['Grossamounttotal']=$runninggrosstotal;
             
             $this->htmltable .= '<tfoot><tr>'
              . '<td colspan="8">Include the Cost of freight (AS A TOTAL) to help apportion it to the cost of inventory</td>'
              . '<td class="number"><input type="text" class="number" name="FREIGHT" value="'.$_POST['FREIGHT'].'" size="10" required="required"/></td>'
              . '</tr></tfoot>';
            
         
        echo $this->htmltable;

    }
    
    
    
     
 }
 
 class FixedAssets {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     
     function GetStockdetails($itemcode){
         global $db;
         
        $sql= "SELECT
                fixedassets.assetid,
                fixedassets.barcode, 
                fixedassets.description, 
                fixedassets.cost, 
                vatcategory.vat 
               FROM fixedassets 
              left join [fixedassetcategories] on fixedassets.[assetcategoryid]=[fixedassetcategories].[categoryid] 
               left join vatcategory on [fixedassetcategories].[vatcategorycode]=vatcategory.vatc
                    where fixedassets.assetid='".$itemcode."'";
         $ResultIndex = DB_query($sql,$db);
         $stocklist = DB_fetch_row($ResultIndex);
         $this->stocklist['itemcode'] = $stocklist[0];
         $this->stocklist['barcode'] = $stocklist[1];
         $this->stocklist['stockname'] = $stocklist[2];
         $this->stocklist['averagestock'] = $stocklist[3];
         $this->stocklist['partperunit'] = 1;
         $this->stocklist['vat'] = $stocklist[4]; ;
         $this->stocklist['si'] = 'PCS' ;
         
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty,$price){
         $StockList = $this->GetStockdetails($itemcode);
         
         $_SESSION['Purchase_orders'][$this->LineNo()]=
            array('itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>'Pcs',
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$qty,
                  'salesprice'=>$price,
                  'netamount'=>$net,
                  'vatamount'=>$vat,
                  'grossamount'=>$gross);
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['Purchase_orders_index'];
     }
     
     function neworder(){
         $_SESSION['Purchase_orders']=array();
         $_SESSION['Purchase_orders_index']=0;
         
     }
     
     function Nextorder(){
         $_SESSION['Purchase_orders_index']++;
    }
    
     function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;

            $results=DB_query("SELECT [itemcode] ,[customer],[phone] ,[email] 
            ,[city] ,[country],[curr_cod],[supplierposting],[VATinclusive],[IsTaxed]
            FROM [creditors] join postinggroups on code=[supplierposting] 
            where itemcode='".$_POST['VendorID']."'", $db);
              
            $debtorsrow = DB_fetch_row($results);
            $this->customerposting = $debtorsrow[7];
            $this->VATinclusive = $debtorsrow[8];
            $this->IsTaxed = $debtorsrow[9];

            
            foreach ($_SESSION['Purchase_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0;
                $cnetamount=0; $cgrossamount=0; $emptyunits=0;
    
                $itemcode = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $rate = ($this->IsTaxed==false)?0:$stocklist['vat'];
    
                if(isset($_POST['quantity'][$itemcode])){
                    $qty = $_POST['quantity'][$itemcode];
                }else{
                    $qty = .0;
                }
        
                if(isset($_POST['salesprice'][$itemcode])){
                    $salesprice = $_POST['salesprice'][$itemcode] ;
                }else{
                    $salesprice = .0;
                }
         
              $baseamount = ($salesprice * $qty);
    
            if($this->VATinclusive==true){
                $netamount   = $baseamount * (1- ($rate/(100+$rate)));
                $vatamount   = $netamount * ($rate/(100+$rate));
                $grossamount = $baseamount ;
            }else{
                $vatamount  = $baseamount * ($rate/100);
                $grossamount= $baseamount + $vatamount;
                $netamount  = $baseamount;
            }
    
                $runningnettotal += $netamount;
                $runningvattotal += $vatamount;
                $runninggrosstotal += $grossamount;
                
                $this->htmltable .= sprintf('<tr>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td class="number"><input type="text" class="integer" name="quantity['.$itemcode.']" value="'.$qty.'" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" size="5"/></td>'
              . '</tr>',trim($stocklist['itemcode']),trim($stocklist['stockname']));
            }
            
                       
            $this->htmltable .= sprintf('<tr>'
              . '<td colspan="4">TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr>', 
                      number_format($runningnettotal,2),
                      number_format($runningvattotal,2),
                      number_format($runninggrosstotal,2));
              $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
        echo $this->htmltable;

    }
    
    
    
     
 }
 
 class FixedAssetsHire {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     
     function GetStockdetails($itemcode){
         global $db;
         
        $sql= "SELECT
                fixedassets.assetid,
                fixedassets.barcode, 
                fixedassets.description, 
                fixedassets.cost, 
                vatcategory.vat ,
                sum([fixedassettrans].[units]) as units
               FROM fixedassets 
               left join [fixedassetcategories] on fixedassets.[assetcategoryid]=[fixedassetcategories].[categoryid] 
               left join vatcategory on [fixedassetcategories].[vatcategorycode]=vatcategory.vatc
               left join [fixedassettrans] on fixedassets.assetid=[fixedassettrans].[assetid]
                where fixedassets.assetid='".$itemcode."' 
                    Group by fixedassets.assetid,
                fixedassets.barcode, 
                fixedassets.description, 
                fixedassets.cost, 
                vatcategory.vat ";
         $ResultIndex = DB_query($sql,$db);
         $stocklist = DB_fetch_row($ResultIndex);
         $this->stocklist['itemcode'] = $stocklist[0];
         $this->stocklist['barcode'] = $stocklist[1];
         $this->stocklist['stockname'] = $stocklist[2];
         $this->stocklist['averagestock'] = $stocklist[3];
         $this->stocklist['partperunit'] = 1;
         $this->stocklist['vat'] = $stocklist[4]; 
         $this->stocklist['si'] = 'PCS' ;
         $this->stocklist['units'] =  $stocklist[5];
         
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty,$price){
         $StockList = $this->GetStockdetails($itemcode);
         
         $_SESSION['Purchase_orders'][$this->LineNo()]=
            array('itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>'Pcs',
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$qty,
                  'units'=>$StockList['units'],
                  'unitbalance'=>$StockList['units']-$qty,
                  'salesprice'=>$price,
                  'netamount'=>$net,
                  'vatamount'=>$vat,
                  'grossamount'=>$gross);
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['Purchase_orders_index'];
     }
     
     function neworder(){
         $_SESSION['Purchase_orders']=array();
         $_SESSION['Purchase_orders_index']=0;
         
     }
     
     function Nextorder(){
         $_SESSION['Purchase_orders_index']++;
    }
    
     function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;

        
   $results=DB_query("SELECT [itemcode],[creditlimit],[customer],[phone] ,[email] ,[city] ,[country],[curr_cod],[customerposting],[salesman],[VATinclusive],[IsTaxed]
                   FROM [debtors] join postinggroups on code=[customerposting] 
                   where itemcode='".$_POST['CustomerID']."'", $db);
            $debtorsrow = DB_fetch_row($results);
            $this->customerposting = $debtorsrow[8];
            $this->VATinclusive = $debtorsrow[10];
            $this->IsTaxed = $debtorsrow[11];
            
            foreach ($_SESSION['Purchase_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0;
                $cnetamount=0; $cgrossamount=0; $emptyunits=0;
    
                $itemcode = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $rate = ($this->IsTaxed==false)?0:$stocklist['vat'];
                $unitsavailable = $stocklist['units'];
                
                if(isset($_POST['quantity'][$itemcode])){
                    $qty = $_POST['quantity'][$itemcode];
                }else{
                    $qty = .0;
                }
        
               $unitsremaining = $unitsavailable-$qty ;
                              
                if(isset($_POST['salesprice'][$itemcode])){
                    $salesprice = $_POST['salesprice'][$itemcode] ;
                }else{
                    $salesprice = .0;
                }
         
              $baseamount = ($salesprice * $qty);
    
            if($this->VATinclusive==true){
                $netamount   = $baseamount * (1- ($rate/(100+$rate)));
                $vatamount   = $netamount * ($rate/(100+$rate));
                $grossamount = $baseamount ;
            }else{
                $vatamount  = $baseamount * ($rate/100);
                $grossamount= $baseamount + $vatamount;
                $netamount  = $baseamount;
            }
    
                $runningnettotal += $netamount;
                $runningvattotal += $vatamount;
                $runninggrosstotal += $grossamount;
                
                $this->htmltable .= sprintf('<tr>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td class="number"><input type="text" class="integer" name="quantity['.$itemcode.']" value="'.$qty.'" size="5"/></td>'
              . '<td class="number"><input type="text" class="integer" name="units['.$itemcode.']" value="'.$unitsavailable.'" size="5" readonly="readonly"/></td>'
              . '<td class="number"><input type="text" class="integer" name="unitbalance['.$itemcode.']" value="'.$unitsremaining.'" size="5" readonly="readonly"/></td>'
              . '<td class="number"><input type="text" class="number" name="salesprice['.$itemcode.']" value="'.$salesprice.'" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="netamount['.$itemcode.']" value="'.$netamount.'" readonly="readonly" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="vatamount['.$itemcode.']" value="'.$vatamount.'" readonly="readonly" size="5"/></td>'
              . '<td class="number"><input type="text" class="number" name="grossamount['.$itemcode.']" value="'.$grossamount.'" readonly="readonly" size="5"/></td>'
              . '</tr>',trim($stocklist['itemcode']),trim($stocklist['stockname']));
            }
            
                       
            $this->htmltable .= sprintf('<tr>'
              . '<td colspan="6">TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr>', 
                      number_format($runningnettotal,2),
                      number_format($runningvattotal,2),
                      number_format($runninggrosstotal,2));
              $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
        echo $this->htmltable;

    }
    
    
    
     
 }
 
?>