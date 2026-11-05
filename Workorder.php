<?php
include('includes/session.inc');
$Title = _('Work Order');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

$URL = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
$allhavetanks = true;
include('purchases/tanktrans.inc');

if(isset($_GET['BOM'])){
  $BOMid=$_GET['BOM'];
}elseif(isset($_POST['BOM'])){
  $BOMid=$_POST['BOM'];
}
        
echo '<p class="page_title_text">' . '<img src="css/'.$Theme.'/images/inventory.png" title="' . _('Work Order') .'" alt="" />' . ' ' . _('Work Order') . '</p>';
echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if(isset($BOMid)){
   echo '<input type="hidden" name="BOM" value="'. $BOMid .'" />';
}

if(isset($_POST['submit'])){
    if(!isset($_POST['Batchno'])){
        $_POST['Batchno'] = GetNextTransNo(28,$db);
    }
    
    if(!isset($_POST['Date'])){
        $ResultIndex = db_query("select sysdate() as date",$db);
        $row = DB_fetch_row($ResultIndex);
        $_POST['Date'] = ConvertSQLDate($row[0]);
    }
    
     $SQL="select [parent_itemcode],[parent_description],[parent_UOM],[batchsize] from [billofmaterial] where [parent_itemcode]='".$BOMid."'";
     $ResultIndex = DB_query($SQL,$db);
     $parentrow = DB_fetch_row($ResultIndex);
     $batchsize = $parentrow[3];
     
    echo '<table class="table table-bordered">';
    echo '<tr><td>Batch No</td><td><input type="text" name="Batchno" value="'.$_POST['Batchno'].'" required="required" size="11" maxlength="10"/></td>';
    echo '<td>Date</td><td><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Date" value="'.$_POST['Date'].'" required="required" size="11" maxlength="10"/></td></tr>';
    echo '<tr class="OddTableRows"><td>Parent BOM</td></tr><tr><td>'.$parentrow[1].'</td><td>'; 
    echo selectUOM($parentrow[0],$parentrow[2]).'</td>'
       . '<td><input type="text"  value="'.$_POST['production'].'" class="number" size="5" name="production" required="required" /></td>';
   
    
    if(Checkrowtank($BOMid)>0){
            echo '<td><select name="productiontank['.$BOMid.']">';
            GetcmbTank($BOMid);
            echo '</select>';
            echo '<input type="hidden" name="store_BOM['.$BOMid.']" value="tank"/></td>';
         } else {
            echo  createstores($BOMid);
            echo '<input type="hidden" name="store_BOM['.$BOMid.']" value="store"/>';
          }
    
    echo '<tr class="OddTableRows">'
            . '<td>Materials</td>'
            . '<td>UOM</td>'
            . '<td class="number">Materials<br/>STD Requirements</td>'
            . '<td class="number">Materials<br/>(Actual)</td>'
            . '<td class="number">Variance</td>'
            . '<td>Store</td>'
            . '<td class="number">Store<br/>Balance</td></tr>';
                     
  $SQL="select [BOM_items].[parent_itemcode],"
          . " [BOM_items].[sequence],"
          . " [BOM_items].[itemcode],"
          . " [BOM_items].[description],"
          . " [BOM_items].[qty],"
          . " [BOM_items].[uom],"
          . " [stockmaster].[partperunit], "
          ." (Case "
          . "when rtrim([BOM_items].[uom])='fulqty' then "
          . "([stockmaster].[averagestock]*[stockmaster].[partperunit]) "
          . "else "
          . "([stockmaster].[averagestock]) end ) as [averagestock]"
          . " from [BOM_items]"
          . " join [stockmaster] on [stockmaster].[itemcode]=[BOM_items].[itemcode]"
          . "  where [parent_itemcode]='".$BOMid."'";
  
  $averagecost=0;
  
  $ResultIndex = DB_query($SQL,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $itemcode = $row['itemcode'];
        $colum1   = ($row['qty'] * $_POST['production'])/$batchsize;
        $averagestock = $row['averagestock'];
         
        if(isset($_POST['requirement'][$itemcode])){
            $colum2 = $_POST['requirement'][$itemcode];
        }else{
            $colum2 = 0;
        }
        
         $colum3 = ($colum1 - $colum2);
         
        if(Checkrowtank($itemcode)>0){
            $colum4 = tank_itemcode_balance($_POST['productiontank'][$itemcode]);
            $colum4 -= $colum2;
        } elseif(Checkstocktype($itemcode)==3){
            $colum4 = $colum2;
        } else {
            $colum4 = getbalance($itemcode,$_POST['location'][$itemcode],$row['uom']);
            $colum4 -= $colum2;
        }
         $averagecost += ($colum2 * $averagestock);
                           
         echo '<tr><td>'.$row['description'].'</td><td>'; 
         echo  selectUOM($row['itemcode'],$row['uom']).'</td>';
         echo  '<td>'.number_format($colum1,2).'</td>' ;
         echo  '<td><input type="text" value="'.$colum2.'" class="number" size="5" name="requirement['.$row['itemcode'].']" /> '
            . 'Value:<input type="text" class="number" size="5" name="AVE['.$row['itemcode'].']" value="'.number_format($colum2 * $row['averagestock'],2).'" readonly="readonly"/></td>' ;
         echo  '<td><input type="text" value="'.$colum3.'" class="number" readonly="readonly" size="5" name="variance['.$row['itemcode'].']" /></td>' ;
        
         if(Checkrowtank($itemcode)>0){
            echo '<td><select name="productiontank['.$itemcode.']">';
            GetcmbTank($itemcode);
            echo '</select>';
            echo '<input type="hidden" name="store_tank['.$itemcode.']" value="tank"/></td>';
         } else {
            echo  createstores($itemcode);
            echo '<input type="hidden" name="store_tank['.$itemcode.']" value="store"/>';
          }
          
         echo  '<td><input type="text"  value="'.$colum4.'" class="number" readonly="readonly" size="5" name="stockbalance['.$row['itemcode'].']" /></td>' ;
         echo '</tr>';
       }
    echo  '<td colspan="2">Cost Of BOM unit</td>'
        . '<td>Total Value:<input type="text" class="number" size="5" name="averagecost" value="'.number_format($averagecost/$_POST['production'],2).'" readonly="readonly"/></td>';
      
       echo '<tr><td>';
       echo '<input type="submit" name="submit" value="Recalculate"/>';
       echo '<input type="submit" name="submit" value="Run Production"/>';
       echo '</td></tr>';
       echo '</table>';
    
} else {
    
        if(isset($BOMid)){
                   $SQL="select "
                           . "[parent_itemcode],"
                           . "[parent_description],"
                           . "[parent_UOM],[batchsize] "
                           . "from [billofmaterial]  "
                   . " where [parent_itemcode]='".$BOMid."'";
                   $ResultIndex = DB_query($SQL,$db);
                   $parentrow = DB_fetch_row($ResultIndex);
                   $batchsize = $parentrow[3];
      
                    echo '<table class="table table-bordered">'
                            . '<tr class="OddTableRows">'
                            . '<td>Parent BOM</td></tr><tr><td>'.$parentrow[1].'</td><td>'; 
                    echo selectUOM($parentrow[0],$parentrow[2]).'</td>'
                            . '<td><input type="text"  value="'.$_POST['production'].'" class="number" size="5" name="production" required="required" />';

                    if(Checkrowtank($BOMid)>0){
                        echo '<td><select name="productiontank['.$BOMid.']">';
                        GetcmbTank($BOMid);
                        echo '</select>';
                        echo '<input type="hidden" name="store_BOM['.$BOMid.']" value="tank"/></td>';
                     } else {
                        echo  createstores($BOMid);
                        echo '<input type="hidden" name="store_BOM['.$BOMid.']" value="store"/>';
                      }     
                    echo '<input type="hidden" name="FUOM['.$BOMid.']" value="'.$parentrow[2].'"/></td></tr>';

                    echo '<tr class="OddTableRows">'
                            . '<td>Materials</td>'
                            . '<td>UOM</td>'
                            . '<td class="number">Materials<br/>STD Requirements</td>'
                            . '<td class="number">Materials<br/>(Actual)</td>'
                            . '<td class="number">Variance</td>'
                            . '<td class="number">Store</td>'
                            . '<td class="number">Stock<br/>Balance</td>'
                            . '</tr>';
          
                        $SQL="select "
                        . "[BOM_items].[parent_itemcode],"
                        . "[BOM_items].[sequence],"
                        . "[BOM_items].[itemcode],"
                        . "[BOM_items].[description],"
                        . "[BOM_items].[qty],"
                        . "[BOM_items].[uom],"
                        ." (Case "
                        . "when rtrim([BOM_items].[uom])='fulqty' then "
                        . "([stockmaster].[averagestock] * [stockmaster].[partperunit]) "
                        . "else "
                        . "([stockmaster].[averagestock]) end ) as [averagestock]"
                        . " from [BOM_items]"
                        . " join [stockmaster] on [stockmaster].[itemcode]=[BOM_items].[itemcode]"
                       . "  where [parent_itemcode]='".$BOMid."'";
                            
                          $averagecost=0;
                          
                          $ResultIndex = DB_query($SQL,$db);
                          while($row = DB_fetch_array($ResultIndex)){
                              $itemcode = $row['itemcode'];
                         //     $averagestock = $row['averagestock']/$_POST['production'];
                              $averagestock=$row['averagestock'];
      
                              $colum1 = ($row['qty'] * $_POST['production'])/$batchsize;
                             
                              if(isset($_POST['requirement'][$itemcode])){
                                  $colum2=$_POST['requirement'][$itemcode];
                              }else{
                                  $colum2=0;
                              }

                            if(Checkrowtank($itemcode)>0){
                                $colum4 = tank_itemcode_balance($_POST['productiontank'][$itemcode]);
                                $colum4 -= $colum2;
                            } elseif(Checkstocktype($itemcode)==3){
                                $colum4 = $colum2;
                            } else {
                                $colum4 = getbalance($itemcode,$_POST['location'][$itemcode],$row['uom']);
                                $colum4 -= $colum2;
                            }
        
                            $colum3=$colum1-$colum2;
                            $averagecost += ($colum2 * $averagestock);
                            
                             echo '<tr><td>'.$row['description'].'(FIFO cost:'.$row['averagestock'].')'.'</td><td>'; 
                             echo selectUOM($row['itemcode'],$row['uom']).'</td>';
                             echo  '<td>'.number_format($colum1,2).'</td>' ;
                             echo  '<td><input type="text"  value="'.$colum2.'" class="number" size="5" name="requirement['.$row['itemcode'].']" />'
                                . ' Value:<input type="text" class="number" size="5"  name="AVE['.$row['itemcode'].']"  value="'.number_format($colum2 * $row['averagestock'],2).'" readonly="readonly"/></td>' ;
                             echo  '<td><input type="text"  value="'.$colum3.'" class="number" readonly="readonly" size="5" name="variance['.$row['itemcode'].']" /></td>' ;

                            if(Checkrowtank($itemcode)>0){
                                echo '<td><select name="productiontank['.$itemcode.']">';
                                GetcmbTank($itemcode);
                                echo '</select>';
                                echo '<input type="hidden" name="store_tank['.$itemcode.']" value="tank"/></td>';
                              } else {
                                 echo  createstores($itemcode);
                                 echo '<input type="hidden" name="store_tank['.$itemcode.']" value="store"/></td>';
                             }
                 
                            echo  '<td><input type="text"  value="'.$colum4.'" class="number" readonly="readonly"  name="stockbalance['.$row['itemcode'].']" size="10"/>'
                                    . '<input type="hidden" name="RUOM['.$itemcode.']" value="'.$row['uom'].'"/></td>' ;
                            echo '</tr>';
    }
    
     echo '<tr><td><input type="submit" name="Calculate" value="Calculate" />';
     echo (($allhavetanks==true)?'<input type="submit" name="submit" value="Confirm Details"/>':'').
         '</td><td colspan="2">Cost Of per BOM unit</td>'
        . '<td>Total Value:<input type="text" class="number" size="5" name="averagecost" value="'.number_format($averagecost/$_POST['production'],2).'" readonly="readonly"/></td>'
        . '</tr>';
  
     echo '</table>';
  
}
  
}

  echo '<div class="container"><P>Select a work order item to Produce</P>'
      . '<table class="table table-bordered"><tr><th>BOM CODE</th><th>BOM NAME</th></tr>';
        $SQL= "Select  
                [billofmaterial].[parent_itemcode],
                [billofmaterial].[parent_description],
                [billofmaterial].[parent_UOM]
                from [billofmaterial]";

                   $ResultIndex = DB_query($SQL,$db);
                   while($row = DB_fetch_array($ResultIndex)){
                        echo sprintf('<tr><td><a href="%s?BOM=%s">%s</a></td>', $URL,$row['parent_itemcode'],$row['parent_itemcode']);
                        echo '<td>'. $row['parent_description'].'</td></tr>'   ;    
                   }

                   echo '</table></div>';

echo '</div></form>';
   
include('includes/footer.inc');
   

?>