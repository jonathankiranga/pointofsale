<?php

 class QUOTES  {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     var  $deleteID;
     var  $tankStore;
     var  $Default=array();
     
     function SaveFooter(){
         
              $filename= 'quotes/'.trim($_POST['documentno']).'.terms';
              file_put_contents($filename,$_POST['paymentterms']."\r\n\r\n",FILE_APPEND);
             
              $filename= 'quotes/'.trim($_POST['documentno']).'.bank';
              file_put_contents($filename,$_POST['bankaccountdetails']."\r\n\r\n",FILE_APPEND);

           
     }
     
     Function IsTankOrStore($ItemCode){
        global $db;
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
          $this->tankStore="S";
       }else{
          $this->tankStore="T";
       }
    }
    
     Function MakeStoreTankForItem($ItemCode,$key){
        global $db;
        
          
        $line='';
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
           $this->tankStore="S";
            if(mb_stristr($ItemCode,'H2O')=='H2O'){
               $line .= '<option></option>';
             }else{
                $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
                while($rows= DB_fetch_array($REsults)){
                     $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$_POST['location'][$key]==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
                  }
           }
       }else{
           $this->tankStore="T";
           $ResultIndex=DB_query(sprintf("select tankname from [ProductionUnit] where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$_POST['location'][$key]==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
           }
       }
        return $line; 
    }
    
      Function MakeDefaultStoreTankForItem($ItemCode,$key){
        global $db;
        
         
        $line='';
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
            
                $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
                while($rows =DB_fetch_array($REsults)){
                     $this->Default[0]=array('code'=>trim($rows['code']),'Storename'=>$rows['Storename']);
                  }
       }else{
           
           $ResultIndex=DB_query(sprintf("select tankname from [ProductionUnit] where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                $this->Default[0]=array('code'=>trim($value['tankname']),'Storename'=>trim($value['tankname']));
           }
       }
       
    }
    
     function GetStockdetails($itemcode){
         global $db;
               
         $this->IsTankOrStore($itemcode);
   
         $ResultIndex = DB_query("SELECT 
                        [stockmaster].[barcode],
                        [stockmaster].[itemcode],
                        [stockmaster].[descrip] as [stockname],
                        [unit].[descrip] as [si],
                        [stockmaster].[averagestock],
                        [stockmaster].[partperunit],
                        [vatcategory].[vat],
                        [c].[itemcode] as [container],
                        [c].[descrip] as [packname],
                        [cv].[vat] as CVAT,
                        [stockmaster].[sellingprice]
                  FROM [stockmaster] 
                  left join [stockmaster] c on [stockmaster].[container]=[c].[itemcode]
                  left join [unit] on [stockmaster].[units]=[unit].[code]
                  left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
                  left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
                  left join [inventorypostinggroup] ci on [c].[postinggroup]=[ci].[code]
                  left join [vatcategory] cv on [ci].[vatcategory]=[cv].[vatc] 
                  where [stockmaster].itemcode='".trim($itemcode)."' ", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode']  = $stocklist[0];
         $this->stocklist['itemcode']  = $stocklist[1];
         $this->stocklist['stockname'] = $stocklist[2];
         $this->stocklist['averagestock'] = $stocklist[4];
         $this->stocklist['partperunit'] = $stocklist[5];
         $this->stocklist['vat'] = $stocklist[6];
         $this->stocklist['container'] = $stocklist[7];
         $this->stocklist['packname'] = $stocklist[8];
         $this->stocklist['CVAT'] = $stocklist[9];
         $this->stocklist['sellingprice'] = $stocklist[10];
         
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty,$uom,$packzize=1,$pricevalue){
         $StockList = $this->GetStockdetails($itemcode);
          
         if(mb_strwidth($StockList['itemcode'])>0){
         $id = trim($StockList['itemcode']);
         $container = SelectContainerToUse($itemcode,$packzize,$uom) ;
    
         $_SESSION['sales_orders'][$id]= array('line_no'=>$id,
                  'itemcode' => $id,
                  'barcode' => $StockList['barcode'],
                  'stockname' => $StockList['stockname'],
                  'uom' => $uom, 
                  'pricevalue' => $pricevalue,
                  'partsperunit' => $packzize,
                  'averagestock' => $StockList['averagestock'],
                  'quantity' => $qty,
                  'container' => $container);
         }
          
         $this->showtable();
     }
      
     function neworder(){
         $_SESSION['sales_orders']=array();
     }
     
    function RemoveOrder($itemcode){
            $this->deleteID=trim($itemcode);
            unset($_SESSION['sales_orders'][$this->deleteID]);
            unset($_POST['stockitemcode']); 
            unset($_POST['qty']); 
            unset($_POST['sp']);
            unset($_POST['units']);
    }
    
    function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;
        
        $results=DB_query("SELECT [customerposting],[VATinclusive],[IsTaxed] FROM [debtors] 
        join postinggroups on code=[customerposting] where itemcode='".$_POST['CustomerID']."'", $db);
        $debtorsrow = DB_fetch_row($results);
        $this->customerposting = $debtorsrow[0]; $this->VATinclusive = $debtorsrow[1]; $this->IsTaxed = $debtorsrow[2];
            
            foreach ($_SESSION['sales_orders'] as $lineno => $rowvalue) {
                $emptyprice=0; $totalemptycost=0; 
                $cvatamount=0; $vatamount=0;
                $cnetamount=0; $cgrossamount=0; 
    
                 
                $line_no   = trim($rowvalue['line_no']);
                $itemcode  = $rowvalue['itemcode'];
                
                $stocklist = $this->GetStockdetails($itemcode);
                $rate      = ($this->IsTaxed==false)?0:$stocklist['vat'];
             
                $UOM       = $rowvalue['uom'];
                $package   =(float) $rowvalue['partsperunit'];
                $this->MakeDefaultStoreTankForItem($itemcode,$lineno);
  
                $location  =isset($_POST['location'][$line_no])? $_POST['location'][$line_no]:($this->Default[0]['code']);
                 
                $salesprice  =(float) $rowvalue['pricevalue'] ;
                $PriceInPricelist = SelectPriceListToUse($itemcode,$package,$UOM) ;
                
                $salesprice = ($salesprice=='')?$PriceInPricelist:$salesprice;
                $qty         =(float) $rowvalue['quantity'] ;
                $baseamount  = ($salesprice * $qty * $package);
                
                 if($this->VATinclusive==true){
                    $netamount = $baseamount /  (($rate+100)/100);
                    $vatamount = $baseamount - $netamount;
                    $grossamount=$baseamount ;
                }else{
                    $vatamount  = $baseamount * ($rate/100);
                    $grossamount= $baseamount + $vatamount;
                    $netamount  = $baseamount;
                }
            
                $runningnettotal += $netamount;
                $runningvattotal += $vatamount;
                $runninggrosstotal += $grossamount;
                
                if($this->tankStore=="T"){
                    $StockBalanceInloose =(int) getTankbalance($location,$itemcode);
                    $_SESSION['stockmasterp'][$itemcode]['Balance']= $StockBalanceInloose;
                }elseif($this->tankStore=="S"){
                    $StockBalanceInloose=(int) getbalance($itemcode,$location,'loosqty');
                    $_SESSION['stockmasterp'][$itemcode]['Balance']=$StockBalanceInloose;
                }
                 
               //  id,bc,name 
                 $ID  = trim($stocklist['itemcode']);
                 $BC  = trim($stocklist['barcode']);
                 $NAME= trim($stocklist['stockname']);
                 $Balance = $StockBalanceInloose;
                 $arrayi = $_SESSION['sales_orders'][$lineno];
            
                 $_SESSION['sales_orders'][$lineno] = array_replace_recursive($arrayi,
                 array(
                     'stockname'=>$NAME,
                     'VATinclusive'=>$this->VATinclusive,
                     'IsTaxed'=>$this->IsTaxed,
                     'vatrate'=>$rate,
                     'salesprice'=>$salesprice,
                     'PriceInPricelist'=>$PriceInPricelist,
                     'stkbalance'=>($Balance-$qty),
                     'netamount'=>$netamount,
                     'vatamount'=>$vatamount,
                     'grossamount'=>$grossamount)
                );
           
                  
                $this->htmltable .= sprintf('<tr onclick="SalesOrderInventorygrid(\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\');">'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td class="number"><input class="number" type="text" name="quantity['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" /></td>'
              . '<td>%s</td>'
              . '<td><input class="number" type="text" name="package['.$line_no.']" value="'.$package.'"  maxlength="5" readonly="readonly" size="7"/></td>'
              . '<td><input type="text" class="number" readonly="readonly" value="'.number_format($salesprice,2).'"  size="10"/></td>'
              . '<td><input type="text" class="number" name="netamount['.$line_no.']" value="'.number_format($netamount,2).'" readonly="readonly" size="10"/></td>'
              . '<td><input type="text" class="number" name="vatamount['.$line_no.']" value="'.number_format($vatamount,2).'" readonly="readonly" size="10"/></td>'
              . '<td><input type="text" class="number" name="grossamount['.$line_no.']" value="'.number_format($grossamount,2).'" readonly="readonly" size="10"/></td>'
              . '</tr>',$ID,$BC,$NAME,$qty,$salesprice,$package, trim($stocklist['barcode']),trim($stocklist['stockname']),getGriduom($line_no,$UOM) );
        
                }
            
              $this->htmltable .= sprintf('<tfoot><tr>'
              . '<td class="number cell" colspan="6">Total Order</td><td class="number cell">%s</td><td class="number cell">%s</td><td class="number cell">%s</td>'
              . '</tr></tfoot>',number_format($runningnettotal,2),  number_format($runningvattotal,2),  number_format($runninggrosstotal,2));
              
            $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
         
            
        echo $this->htmltable;
      

    }
    
    
 }

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
                        [vatcategory].[vat],
                        [c].[itemcode] as [container],
                        [c].[descrip] as [packname],
                        [cv].[vat] as CVAT
                  FROM [stockmaster] left join [stockmaster] c on [stockmaster].[container]=[c].[itemcode]
                  left join [unit] on [stockmaster].[units]=[unit].[code]
                  left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
                  left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
                  left join [inventorypostinggroup] ci on [c].[postinggroup]=[ci].[code]
                  left join [vatcategory] cv on [ci].[vatcategory]=[cv].[vatc]
                  where [stockmaster].itemcode='".$itemcode."'", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode']= $stocklist[0];
         $this->stocklist['itemcode']= $stocklist[1];
         $this->stocklist['stockname']= $stocklist[2];
         $this->stocklist['si']= $stocklist[3];
         $this->stocklist['averagestock']= $stocklist[4];
         $this->stocklist['partperunit']= $stocklist[5];
         $this->stocklist['vat']= $stocklist[6];
         $this->stocklist['container']= $stocklist[7];
         $this->stocklist['packname']= $stocklist[8];
         $this->stocklist['CVAT']= $stocklist[9];
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty,$price,$price_empties){
         $StockList = $this->GetStockdetails($itemcode);
         $UOM = $_POST['UOM']['$itemcode'];
         $line_No = $this->LineNo();
         
         $_SESSION['sales_orders'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>$UOM,
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$qty,
                  'salesprice'=>$price,
                  'emptycost'=>$price_empties,
                  'totalemptycost'=>$total_empties,
                  'netamount'=>$net,
                  'vatamount'=>$vat,
                  'grossamount'=>$gross);
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['sales_orders_index'];
     }
     
     function neworder(){
         $_SESSION['sales_orders']=array();
         $_SESSION['sales_orders_index']=0;
         
     }
     
     function Nextorder(){
         $_SESSION['sales_orders_index']++;
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

            
            foreach ($_SESSION['sales_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0;
                $cnetamount=0; $cgrossamount=0; $emptyunits=0;
    
                $line_no = $rowvalue['line_no'];
                $itemcode = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                
                $containercode = trim($stocklist['container']);
                $rate = ($this->IsTaxed==false)?0:$stocklist['vat'];
    
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
    
                if($_POST['UOM'][$line_no]=='fulqty'){
                     $baseamount = ($salesprice * ($qty * $stocklist['partperunit']));

                       if(isset($_POST['emptycost'][$line_no])){
                         $emptycost=$_POST['emptycost'][$line_no];
                       }
                       
                       $emptyunits = $qty;
                       $totalemptycost = ($emptycost * $emptyunits);             
                       $crate = $stocklist['CVAT'];

                        if($VATinclusive==true){
                             $cvatamount =  $totalemptycost * ($crate/100+$crate);
                             $cgrossamount=  $totalemptycost ;
                        }else{
                            $cvatamount =  $totalemptycost * ($crate/100);
                            $cgrossamount=  $totalemptycost + $cvatamount;
                        }

                 }else{
                   $baseamount= ($salesprice * $qty);
                }
          
    
            if($this->VATinclusive==true){
                $netamount = $baseamount /  round((($rate+100)/100),2);
                $vatamount = $baseamount - $netamount;
                $grossamount=$baseamount ;
            }else{
                $vatamount  = $baseamount * ($rate/100);
                $grossamount= $baseamount + $vatamount;
                $netamount  = $baseamount;
            }
    
                $netamount += $totalemptycost;
                $vatamount += $cvatamount;
                $grossamount +=$cgrossamount;

                $runningnettotal += $netamount;
                $runningvattotal += $vatamount;
                $runninggrosstotal += $grossamount;
                
                $this->htmltable .= sprintf('<tr>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="text" class="integer" name="quantity['.$line_no.']" value="'.$qty.'" size="5"/>'
              . '<input type="hidden"  name="itemcode['.$line_no.']" value="'.$stocklist['itemcode'].'"/></td>'
              . '<td><input type="text" class="number" name="salesprice['.$line_no.']" value="'.$salesprice.'" size="5"/></td>'
              . '<td><input type="text" class="number" name="emptycost['.$line_no.']" value="'.$emptycost.'" size="5"/>'
              . '<input type="hidden" name="emptycode['.$line_no.']" value="'.$containercode.'" size="1"/>'
              . '<input type="hidden" name="emptyunits['.$line_no.']" value="'.$emptyunits.'" size="1"/></td>'
              . '<td><input type="text" class="number" name="totalemptycost['.$line_no.']" value="'.$totalemptycost.'" readonly="readonly" size="5"/></td>'
              . '<td><input type="text" class="number" name="netamount['.$line_no.']" value="'.$netamount.'" readonly="readonly" size="8"/></td>'
              . '<td><input type="text" class="number" name="vatamount['.$line_no.']" value="'.$vatamount.'" readonly="readonly" size="8"/></td>'
              . '<td><input type="text" class="number" name="grossamount['.$line_no.']" value="'.$grossamount.'" readonly="readonly" size="8"/></td>'
             . '</tr>',trim($stocklist['barcode']), 
                        trim($stocklist['stockname']),
                        CreateUnits($itemcode,$line_no), 
                        number_format($stocklist['partperunit'],0),
                        number_format($stocklist['averagestock'],2));
            }
            
            $this->htmltable .= sprintf('<tfoot><tr>'
              . '<td class="number" colspan="9">Total Order + Cost of applicable Empties</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>',number_format($runningnettotal,2),
                    number_format($runningvattotal,2), 
                    number_format($runninggrosstotal,2));
            $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
        echo $this->htmltable;

    }
       
 }
 
 class serviceinvoice {
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
                        [vatcategory].[vat],
                        [c].[itemcode] as [container],
                        [c].[descrip] as [packname],
                        [cv].[vat] as CVAT
                  FROM [stockmaster] left join [stockmaster] c on [stockmaster].[container]=[c].[itemcode]
                  left join [unit] on [stockmaster].[units]=[unit].[code]
                  left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
                  left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
                  left join [inventorypostinggroup] ci on [c].[postinggroup]=[ci].[code]
                  left join [vatcategory] cv on [ci].[vatcategory]=[cv].[vatc]
                  where [stockmaster].itemcode='".$itemcode."'", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode']= $stocklist[0];
         $this->stocklist['itemcode']= $stocklist[1];
         $this->stocklist['stockname']= $stocklist[2];
         $this->stocklist['averagestock']= $stocklist[4];
         $this->stocklist['vat']= $stocklist[6];
         $this->stocklist['CVAT']= $stocklist[9];
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty,$price){
         $StockList = $this->GetStockdetails($itemcode);
         $UOM       = $_POST['UOM']['$itemcode'];
         $line_No   = $this->LineNo();
         
         $_SESSION['sales_orders'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$qty,
                  'salesprice'=>$price,
                  'netamount'=>$net,
                  'vatamount'=>$vat,
                  'grossamount'=>$gross);
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['sales_orders_index'];
     }
     
     function neworder(){
         $_SESSION['sales_orders']=array();
         $_SESSION['sales_orders_index']=0;
         
     }
     
     function Nextorder(){
         $_SESSION['sales_orders_index']++;
    }
    
    function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;

            $results=DB_query("SELECT 
                [itemcode],
                [creditlimit],
                [customer],
                [phone] ,
                [email] ,
                [city] ,
                [country],
                [curr_cod],
                [customerposting],
                [salesman],
                [VATinclusive],
                [IsTaxed]
                FROM [debtors] join postinggroups on code=[customerposting] 
                where itemcode='".$_POST['CustomerID']."'", $db);
            $debtorsrow = DB_fetch_row($results);
            $this->customerposting = $debtorsrow[8];
            $this->VATinclusive = $debtorsrow[10];
            $this->IsTaxed = $debtorsrow[11];

            
            foreach ($_SESSION['sales_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0;
                $cnetamount=0; $cgrossamount=0; $emptyunits=0;
    
                $line_no = $rowvalue['line_no'];
                $itemcode = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                $rate = ($this->IsTaxed==false)?0:$stocklist['vat'];
    
                if(isset($_POST['quantity'][$line_no])){
                    $qty= $_POST['quantity'][$line_no];
                }else{
                    $qty=1;
                }
        
                if(isset($_POST['salesprice'][$line_no])){
                    $salesprice= $_POST['salesprice'][$line_no] ;
                }else{
                    $salesprice=.0;
                }
    
          
               $baseamount= ($salesprice * $qty);
               
          
    
            if($this->VATinclusive==true){
                $netamount = $baseamount /  round((($rate+100)/100),2);
                $vatamount = $baseamount - $netamount;
                $grossamount=$baseamount ;
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
              . '<td><input type="text" class="integer" name="quantity['.$line_no.']" value="'.$qty.'" size="5"/>'
              . '<input type="hidden"  name="itemcode['.$line_no.']" value="'.$stocklist['itemcode'].'"/></td>'
              . '<td><input type="text" class="number" name="salesprice['.$line_no.']" value="'.$salesprice.'" size="5"/></td>'
              . '<td><input type="text" class="number" name="netamount['.$line_no.']" value="'.$netamount.'" readonly="readonly" size="8"/></td>'
              . '<td><input type="text" class="number" name="vatamount['.$line_no.']" value="'.$vatamount.'" readonly="readonly" size="8"/></td>'
              . '<td><input type="text" class="number" name="grossamount['.$line_no.']" value="'.$grossamount.'" readonly="readonly" size="8"/></td>'
             . '</tr>',trim($stocklist['barcode']), 
                        trim($stocklist['stockname']),   
                        CreateUnits($itemcode,$line_no));
            }
            
            $this->htmltable .= sprintf('<tfoot><tr>'
              . '<td class="number" colspan="5">Total Order</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>',
                    number_format($runningnettotal,2),
                    number_format($runningvattotal,2), 
                    number_format($runninggrosstotal,2));
            $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
        echo $this->htmltable;

    }
       
 }
 
 class Labtests {
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
                        [vatcategory].[vat],
                        [c].[itemcode] as [container],
                        [c].[descrip] as [packname],
                        [cv].[vat] as CVAT
                  FROM [stockmaster] left join [stockmaster] c on [stockmaster].[container]=[c].[itemcode]
                  left join [unit] on [stockmaster].[units]=[unit].[code]
                  left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
                  left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
                  left join [inventorypostinggroup] ci on [c].[postinggroup]=[ci].[code]
                  left join [vatcategory] cv on [ci].[vatcategory]=[cv].[vatc]
                  where [stockmaster].itemcode='".$itemcode."'", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode']= $stocklist[0];
         $this->stocklist['itemcode']= $stocklist[1];
         $this->stocklist['stockname']= $stocklist[2];
         $this->stocklist['si']= $stocklist[3];
         $this->stocklist['averagestock']= $stocklist[4];
         $this->stocklist['partperunit']= $stocklist[5];
         $this->stocklist['vat']= $stocklist[6];
         $this->stocklist['container']= $stocklist[7];
         $this->stocklist['packname']= $stocklist[8];
         $this->stocklist['CVAT']= $stocklist[9];
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty){
         $StockList = $this->GetStockdetails($itemcode);
         $line_No   = $this->LineNo();
         $store     = $_POST['location'][$line_no];
         $UOM       = $_POST['UOM'][$line_no];
          
         $_SESSION['labreagents'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>$UOM,
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
                $store     = $_POST['location'][$line_no];
                $UOM       = $_POST['UOM'][$line_no];
                
                if(isset($_POST['quantity'][$line_no])){
                    $qty = $_POST['quantity'][$line_no];
                }else{
                    $qty = 0;
                }
                 
                $this->htmltable .= sprintf('<tr>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="text" class="integer" name="quantity['.$line_no.']" value="'.$qty.'" size="5"/>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<input type="hidden"  name="itemcode['.$line_no.']" value="'.$stocklist['itemcode'].'"/></td>'
              . '</tr>',trim($stocklist['barcode']), 
                        trim($stocklist['stockname']),
                        CreateUnits($itemcode,$line_no), 
                        number_format($stocklist['partperunit'],0),
                        createstores($line_no),
                        getbalance($itemcode,$store,$UOM));
            }
        echo $this->htmltable;

    }
       
 }
 
 class Quote {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     
     function GetLabTestdetails($sampletypeid,$qty=0,$price=0){
        Global $db;
        
        $line_No=0;
        $_SESSION['sales_orders']=array();
    
         $sql="SELECT 
                [LaboratoryStandards].[Parameter] as [stockname],
                [stockmaster].[barcode],
                ltrim([LaboratoryStandards].[sampletypeid])+ltrim([LaboratoryStandards].[ParameterID]) as itemcode,
                [unit].[descrip] as [si],
                [stockmaster].[averagestock],
                [stockmaster].[partperunit],
                [vatcategory].[vat]
            FROM [LaboratoryStandards]
            join [SampleTypes] on [SampleTypes].[sampletypeid]=[LaboratoryStandards].[sampletypeid]
            join [stockmaster] on [SampleTypes].[salesgroup]=[stockmaster].[itemcode]
            left join [unit] on [stockmaster].[units]=[unit].[code]
            left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
            left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
          where [LaboratoryStandards].[sampletypeid]='".$sampletypeid."'";
         
       $ResultIndex = DB_query($sql, $db);
       while($StockList=DB_fetch_array($ResultIndex)){  
        
          $_SESSION['sales_orders'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>html_entity_decode($StockList['stockname']),
                  'si'=>$StockList['si'],
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'emptycost'=>0,
                  'totalemptycost'=>0,
                  'quantity'=>$_POST['quantity'][$line_No],
                  'salesprice'=>$_POST['salesprice'][$line_No],
                  'netamount'=>$_POST['netamount'][$line_No],
                  'vatamount'=>$_POST['vatamount'][$line_No],
                  'grossamount'=>$_POST['grossamount'][$line_No],
                  'quotetype'=>'test',
                  'CVAT'=>$StockList['vat'],
                  'packname'=>'',
                  'container'=>'');
         
                 $line_No ++;
       }
       
        
         $this->showtable();
         
     }
     
     function GetStockdetails($itemcode){
         global $db;
         
         $ResultIndex = DB_query("SELECT 
                        [stockmaster].[barcode],
                        [stockmaster].[itemcode],
                        [stockmaster].[descrip] as [stockname],
                        [unit].[descrip] as [si],
                        [stockmaster].[averagestock],
                        [stockmaster].[partperunit],
                        [vatcategory].[vat],
                        [c].[itemcode] as [container],
                        [c].[descrip] as [packname],
                        [cv].[vat] as CVAT
                  FROM [stockmaster] left join [stockmaster] c on [stockmaster].[container]=[c].[itemcode]
                  left join [unit] on [stockmaster].[units]=[unit].[code]
                  left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
                  left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
                  left join [inventorypostinggroup] ci on [c].[postinggroup]=[ci].[code]
                  left join [vatcategory] cv on [ci].[vatcategory]=[cv].[vatc]
                  where [stockmaster].itemcode='".$itemcode."'", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode']= $stocklist[0];
         $this->stocklist['itemcode']= $stocklist[1];
         $this->stocklist['stockname']= $stocklist[2];
         $this->stocklist['si']= $stocklist[3];
         $this->stocklist['averagestock']= $stocklist[4];
         $this->stocklist['partperunit']= $stocklist[5];
         $this->stocklist['vat']= $stocklist[6];
         $this->stocklist['container']= $stocklist[7];
         $this->stocklist['packname']= $stocklist[8];
         $this->stocklist['CVAT']= $stocklist[9];
         return $this->stocklist;
         
     }
     
     function GetAssetdetails($itemcode){
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
         $this->stocklist['si'] = 'UNIT' ;
         $this->stocklist['CVAT'] = $stocklist[4];
        
         return $this->stocklist;
         
     }
    
     function Getitems($itemcode,$qty,$price,$price_empties){
         $StockList = $this->GetStockdetails($itemcode);
         
         $line_No = $this->LineNo();
         
         $_SESSION['sales_orders'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>$_POST['UOM'][$line_No],
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'emptycost'=>$price_empties,
                  'totalemptycost'=>$total_empties,
                  'quantity'=>$_POST['quantity'][$line_No],
                  'salesprice'=>$_POST['salesprice'][$line_No],
                  'netamount'=>$_POST['netamount'][$line_No],
                  'vatamount'=>$_POST['vatamount'][$line_No],
                  'grossamount'=>$_POST['grossamount'][$line_No],
                  'quotetype'=>'stock',
                  'CVAT'=>$StockList['CVAT'],
                  'packname'=>$StockList['packname'],
                  'container'=>$StockList['container']);
        
         $this->showtable();
     }
 
     function Getassets($itemcode,$qty,$price){
         $StockList = $this->GetAssetdetails($itemcode);
         $line_No = $this->LineNo();
         $_SESSION['sales_orders'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'si'=>$_POST['UOM'][$line_No],
                  'partperunit'=>$StockList['partperunit'],
                  'averagestock'=>$StockList['averagestock'],
                  'quantity'=>$_POST['quantity'][$line_No],
                  'salesprice'=>$_POST['salesprice'][$line_No],
                  'netamount'=>$_POST['netamount'][$line_No],
                  'vatamount'=>$_POST['vatamount'][$line_No],
                  'grossamount'=>$_POST['grossamount'][$line_No],
                  'quotetype'=>'assets',
                  'CVAT'=>$StockList['CVAT'],
                  'packname'=>$StockList[''],
                  'container'=>$StockList['']);
        
         $this->showtable();
     }
         
     function PUTitems($quotetype,$itemcode,$qty,$price,$price_empties=''){
       
            if($quotetype=='stock'){
             $this->Getitems($itemcode,$qty,$price,$price_empties);
            }elseif($quotetype=='assets'){
             $this->Getassets($itemcode,$qty,$price);
            }elseif($quotetype=='test'){
             $this->GetLabTestdetails($itemcode,$qty,$price);
            }
        
    }
          
     function LineNo(){
         return $_SESSION['sales_orders_index'];
     }
     
     function neworder(){
         $_SESSION['sales_orders']=array();
         $_SESSION['sales_orders_index']=0;
         
     }
     
     function Nextorder(){
         $_SESSION['sales_orders_index']++;
    }
    
     function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $runningvattotal = 0;
        $runninggrosstotal = 0;

            $results=DB_query("SELECT [itemcode],[creditlimit],[customer],[phone]
                ,[email] ,[city] ,[country],[curr_cod],[customerposting],[salesman],[VATinclusive],[IsTaxed]
                   FROM [debtors] join postinggroups on code=[customerposting] 
                   where itemcode='".$_POST['CustomerID']."'", $db);
            $debtorsrow = DB_fetch_row($results);
            $this->customerposting = $debtorsrow[8];
            $this->VATinclusive = $debtorsrow[10];
            $this->IsTaxed = $debtorsrow[11];

            
            foreach ($_SESSION['sales_orders'] as $lineno => $rowvalue) {
                $emptycost=0; $totalemptycost=0; $cvatamount =0;
                $cnetamount=0; $cgrossamount=0; $emptyunits=0;
 
                $line_no  = $rowvalue['line_no'];
                $itemcode = $rowvalue['itemcode'];
                $rate = ($this->IsTaxed==false)?0:$rowvalue['vat'];
    
                if(isset($_POST['quantity'][$line_no])){
                    $qty= $_POST['quantity'][$line_no];
                }else{
                    $qty=1;
                }
        
                if(isset($_POST['salesprice'][$line_no])){
                    $salesprice= $_POST['salesprice'][$line_no] ;
                }else{
                    $salesprice=.0;
                }
    
                
                if($_POST['UOM'][$line_no]=='fulqty'){
                     $baseamount = ($salesprice * ($qty * $rowvalue['partperunit']));
                }else{
                     $baseamount= ($salesprice * $qty);
                }
          
    
            if($this->VATinclusive==true){
                $netamount = $baseamount /  round((($rate+100)/100),2);
                $vatamount = $baseamount - $netamount;
                $grossamount=$baseamount ;
            }else{
                $vatamount  = $baseamount * ($rate/100);
                $grossamount= $baseamount + $vatamount;
                $netamount  = $baseamount;
            }
    
                $netamount += $totalemptycost;
                $vatamount += $cvatamount;
                $grossamount +=$cgrossamount;

                $runningnettotal += $netamount;
                $runningvattotal += $vatamount;
                $runninggrosstotal += $grossamount;
                
                $this->htmltable .= sprintf('<tr>'
               . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="text" class="integer" name="quantity['.$line_no.']" value="'.$qty.'" size="5"/>'
              . '<input type="hidden"  name="itemcode['.$line_no.']" value="'.$stocklist['itemcode'].'"/></td>'
              . '<td><input type="text" class="number" name="salesprice['.$line_no.']" value="'.$salesprice.'" size="5"/></td>'
              . '<td><input type="text" class="number" name="netamount['.$line_no.']" value="'.$netamount.'" readonly="readonly" size="8"/></td>'
              . '<td><input type="text" class="number" name="vatamount['.$line_no.']" value="'.$vatamount.'" readonly="readonly" size="8"/></td>'
              . '<td><input type="text" class="number" name="grossamount['.$line_no.']" value="'.$grossamount.'" readonly="readonly" size="8"/></td>'
              . '</tr>',trim($rowvalue['stockname']),
                        CreateUnits($itemcode,$line_no), 
                        number_format($rowvalue['partperunit'],0),
                        number_format($rowvalue['averagestock'],2));
            }
            
            $this->htmltable .= sprintf('<tfoot><tr>'
              . '<td class="number" colspan="6">Total Order </td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>',number_format($runningnettotal,2),
                    number_format($runningvattotal,2), 
                    number_format($runninggrosstotal,2));
            
            $_SESSION['Grossamounttotal']=$runninggrosstotal;
            
        echo $this->htmltable;

    }
    
    
 }

 ?>