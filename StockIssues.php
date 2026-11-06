<?php
include('includes/session.inc');
$Title = _('Stock Issue');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

$allhavetanks = true;
$URL = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
$Home_Currency = $_SESSION['CompanyRecord']['currencydefault'];
echo '<p class="page_title_text"><img src="css/'.$Theme.'/images/inventory.png" title="' . _('Stock Transfer') . '" alt="" />' . ' ' . _('Stock Issue') . '</p>';

if(isset($_GET['BOM'])){
  $BOMid = $_GET['BOM'];
}elseif(isset($_POST['BOM'])){
  $BOMid = $_POST['BOM'];
}

if($_POST['submit']=='ISSUE STOCK'){
   include('production/issueforbom.inc');
 }

echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div class="container">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';

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
    
     $SQL="select [parent_itemcode],[parent_description],[parent_UOM],[batchsize] "
             . "from [billofmaterial] where [parent_itemcode]='".$BOMid."'";
     $ResultIndex = DB_query($SQL,$db);
     $parentrow = DB_fetch_row($ResultIndex);
     $batchsize = $parentrow[3];
     
    echo '<table class="table table-bordered">';
    echo '<tr><td colspan="2">Batch No</td><td><input type="text" name="Batchno" value="'.$_POST['Batchno'].'" required="required" size="11" maxlength="10"/></td>';
    echo '<td>Date</td><td colspan="3"><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="Date" value="'.$_POST['Date'].'" required="required" size="11" maxlength="10"/></td></tr>';
    echo '<tr><td>Issues for BOM</td><td colspan="7">'.$parentrow[1].'<input type="hidden"  value="'.$batchsize.'" class="number" size="5" name="production" readonly="readonly"/>';
    echo '<input type="hidden" name="FUOM['.$BOMid.']" value="'.$parentrow[2].'"/></td></tr>';
    
    echo '<tr>'
            . '<td>Materials</td>'
            . '<td>UOM</td>'
            . '<td class="number">Materials<br/>Budget</td>'
            . '<td class="number">Materials<br/>(Used)</td>'
            . '<td class="number">Requesting<br/>Materials</td>'
            . '<td class="number">Balance</td>'
            . '<td class="number">Store</td>'
            . '<td class="number">Stock<br/>Balance</td>'
            . '</tr>';
    
    
                     
  $SQL="select "
          . " rtrim([BOM_items].[parent_itemcode]) as parent_itemcode,"
          . " [BOM_items].[sequence],"
          . " rtrim([BOM_items].[itemcode]) as itemcode,"
          . " rtrim([BOM_items].[description]) as description,"
          . " [BOM_items].[qty],"
          . " [BOM_items].[uom],"
          . " [stockmaster].[partperunit], "
          . " (Case "
          . " when rtrim([BOM_items].[uom])='fulqty' then "
          . " ([stockmaster].[averagestock] * [stockmaster].[partperunit]) "
          . " else "
          . " ([stockmaster].[averagestock]) end ) as [averagestock]"
          . " from [BOM_items]"
          . " join [stockmaster] on [stockmaster].[itemcode]=[BOM_items].[itemcode]"
          . " where [parent_itemcode]='".$BOMid."'";
  
  $averagecost=0;
  
  $ResultIndex = DB_query($SQL,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $itemcode = $row['itemcode'];
        $colum1   = $row['qty'] ;
        $averagestock = $row['averagestock'];
        $AlreadyIssued = 0;
           
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
        
        $AlreadyIssued = getBOMissued($itemcode,$BOMid,$row['uom']);
        $AlreadyIssued += getBOMTankissued($itemcode,$BOMid,$row['uom']);
        $colum3 = ($colum1 - ($colum2 + $AlreadyIssued));
        $averagecost += ($colum2 * $averagestock);
                            
            echo '<tr><td>'.$row['description'].'(FIFO cost:'.$row['averagestock'].')'.'</td><td>'; 
            echo  selectUOM($row['itemcode'],$row['uom']).'</td>';
            echo  '<td>'.number_format($colum1,2).'</td>' ;
            echo  '<td>'.number_format($AlreadyIssued,0).'</td>' ;
            echo  '<td><input type="text"  value="'.$colum2.'" class="number" size="5" name="requirement['.$itemcode.']" />'
               . ' Value:'.number_format($colum2 * $row['averagestock'],2).'<input type="hidden" class="number" size="5"  name="AVE['.$itemcode.']"  value="'.($colum2 * $row['averagestock']).'" readonly="readonly"/></td>' ;
            echo  '<td><input type="text"  value="'.$colum3.'" class="number" readonly="readonly" size="5" name="variance['.$itemcode.']" /></td>' ;

         if(Checkrowtank($itemcode)>0){
            echo '<td><select name="productiontank['.$itemcode.']"   required="required"  onchange="ReloadForm(salesform.submit[0])">';
            GetcmbTank($itemcode);
            echo '</select>';
            echo '<input type="hidden" name="store_tank['.$itemcode.']" value="tank"/></td>';
         } else {
            echo  create_stores($itemcode);
            echo '<input type="hidden" name="store_tank['.$itemcode.']" value="store"/>';
          }
          
         echo  '<td><input type="text"  value="'.$colum4.'" class="number" readonly="readonly" size="5" name="stockbalance['.$row['itemcode'].']" /></td>' ;
         echo '</tr>';
       }
      
       
       
         echo '<tr><td><input type="submit" name="submit" value="Calculate" />';
     echo (($allhavetanks==true)?'<input type="submit" name="submit" value="ISSUE STOCK"/>':'').
         '</td><td colspan="2">Additional Cost Of BOM</td>'
        . '<td colspan="5">Value:'.number_format($averagecost,2).'<input type="hidden" class="number" size="5" name="averagecost" value="'.($averagecost).'" /></td>'
        . '</tr>';
  
       echo '</table>';
    
} else {
    
if(isset($BOMid)){
                   
     $SQL="select [parent_itemcode],[parent_description],[parent_UOM],[batchsize] from [billofmaterial] where [parent_itemcode]='".$BOMid."'";
     $ResultIndex = DB_query($SQL,$db);
     $parentrow = DB_fetch_row($ResultIndex);
     $batchsize = $parentrow[3];
     $averagecost = 0;

     echo '<table class="table table-bordered"><tr>'
        . '<th>Issues for BOM</th><th colspan="7">'.$parentrow[1].'<input type="hidden"  value="'.$batchsize.'" class="number" size="5" name="production" readonly="readonly"/>';
     echo '<input type="hidden" name="FUOM['.$BOMid.']" value="'.$parentrow[2].'"/></tH></tr>';

                    echo '<tr>'
                            . '<td>Materials</td>'
                            . '<td>UOM</td>'
                            . '<td class="number">Materials<br/>Budget</td>'
                            . '<td class="number">Materials<br/>(Used)</td>'
                            . '<td class="number">Requesting<br/>Materials</td>'
                            . '<td class="number">Balance</td>'
                            . '<td class="number">Store</td>'
                            . '<td class="number">Stock<br/>Balance</td>'
                            . '</tr>';
          
                        $SQL="select "
                        . "RTRIM([BOM_items].[parent_itemcode]) as parent_itemcode,"
                        . "[BOM_items].[sequence],"
                        . "RTRIM([BOM_items].[itemcode]) as itemcode,"
                        . "RTRIM([BOM_items].[description]) as description,"
                        . "[BOM_items].[qty],"
                        . "[BOM_items].[uom],"
                        ." (Case "
                        . " when rtrim([BOM_items].[uom])='fulqty' then "
                        . "([stockmaster].[averagestock] * [stockmaster].[partperunit]) "
                        . " else "
                        . "([stockmaster].[averagestock]) end ) as [averagestock]"
                        . " from [BOM_items]"
                        . " join [stockmaster] on [stockmaster].[itemcode]=[BOM_items].[itemcode]"
                       . "  where [parent_itemcode]='".$BOMid."'";
                            
                       
                          
                          $ResultIndex = DB_query($SQL,$db);
                          while($row = DB_fetch_array($ResultIndex)){
                              $itemcode = $row['itemcode'];
                              $averagestock = $row['averagestock'];
                              $colum1 = $row['qty'] ;
                              $AlreadyIssued = 0;
                             
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
                            
                            $AlreadyIssued = getBOMissued($itemcode,$BOMid,$row['uom']);
                            $AlreadyIssued += getBOMTankissued($itemcode,$BOMid,$row['uom']);
                            $colum3 = ($colum1 - ($colum2+$AlreadyIssued));
                            $averagecost += ($colum2 * $averagestock);
                            
                             echo '<tr><td>'.$row['description'].'(FIFO cost:'.$row['averagestock'].')'.'</td><td>'; 
                             echo selectUOM($row['itemcode'],$row['uom']).'</td>';
                             echo  '<td>'.number_format($colum1,2).'</td>' ;
                             echo  '<td>'.number_format($AlreadyIssued,0).'</td>' ;
                             echo  '<td><input type="text"  value="'.$colum2.'" class="number" size="5" name="requirement['.$row['itemcode'].']" />'
                                . ' Value:'.number_format($colum2 * $row['averagestock'],2).'<input type="hidden" class="number" size="5"  name="AVE['.$row['itemcode'].']"  value="'.($colum2 * $row['averagestock']).'" readonly="readonly"/></td>' ;
                             echo  '<td><input type="text"  value="'.$colum3.'" class="number" readonly="readonly" size="5" name="variance['.$row['itemcode'].']" /></td>' ;

                            if(Checkrowtank($itemcode)>0){
                                echo '<td><select name="productiontank['.$itemcode.']"  required="required"  onchange="ReloadForm(salesform.Calculate)">';
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
         '</td><td colspan="2">Additional Cost Of BOM</td>'
        . '<td colspan="5">Value:'.number_format($averagecost,2).'<input type="hidden" class="number" size="5" name="averagecost" value="'.($averagecost).'" /></td>'
        . '</tr>';
  
     echo '</table>';
  
}

echo '<div class="container"><P>Select a BOM you want to issue materilas for</P>'
      . '<table class="table table-bordered"><tr><th>BOM CODE</th><th>BOM NAME</th></tr>';

$SQL= "Select  
        [billofmaterial].[parent_itemcode],
        [billofmaterial].[parent_description],
        [billofmaterial].[parent_UOM]
        from [billofmaterial] 
        join stockmaster on [stockmaster].[itemcode]=[billofmaterial].[parent_itemcode]
        where [inactive]=0 ";

    $ResultIndex = DB_query($SQL,$db);
    while($row = DB_fetch_array($ResultIndex)){
         echo sprintf('<tr><td><a href="%s?BOM=%s">%s</a></td>', $URL,$row['parent_itemcode'],$row['parent_itemcode']);
         echo '<td>'. $row['parent_description'].'</td></tr>'   ;    
    }

echo '</table></div>';
}
echo '</div></form>' ;
   
include('includes/footer.inc');


    

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
      
       $REsults=DB_query('SELECT '
               . 'rtrim([code]) as code,'
               . 'rtrim([Storename]) as Storename '
               . 'FROM [Stores]', $db);
       $line .='<td><select name="location['.$code.']" required="required" onchange="ReloadForm(salesform.Calculate)">';
              
       while($rows=  DB_fetch_array($REsults)){
           $line .= sprintf('<option value="%s" %s >%s</option>',
                   $rows['code'],
                   ($_POST['location'][$code]==$rows['code']?'selected="selected"':''),
                   $rows['Storename']);
       }
       $line .= '</select></td>';
       
       
        return $line;
    }
    
function create_stores($code){
       global $db;
       $line="";
      
       $REsults=DB_query('SELECT '
               . 'rtrim([code]) as code,'
               . 'rtrim([Storename]) as Storename '
               . 'FROM [Stores]', $db);
       $line .='<td><select name="location['.$code.']" required="required" onchange="ReloadForm(salesform.submit[0])">';
              
       while($rows=  DB_fetch_array($REsults)){
           $line .= sprintf('<option value="%s" %s >%s</option>',
                   $rows['code'],
                   ($_POST['location'][$code]==$rows['code']?'selected="selected"':''),
                   $rows['Storename']);
       }
       $line .= '</select></td>';
       
       
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
                    ON stockledger.itemcode=stockmaster.itemcode
                    AND stockledger.store='".$store."'
                WHERE stockmaster.itemcode='".$itemcode."'
                GROUP BY stockmaster.partperunit";

        $ResultIndex = DB_query($sql,$db);
        if(DB_num_rows($ResultIndex) < 1){
            return 0.0;
        }

        $rows = DB_fetch_row($ResultIndex);
        $baseUnits = (float)$rows[0];
        $partPerUnit = (float)$rows[1];

        if($UOM === 'fulqty'){
            return ($partPerUnit > 0) ? ($baseUnits / $partPerUnit) : 0.0;
        }

        return $baseUnits;
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

function getBOMbalance($stcode,$bqstcode,$UOM=''){
        global $db;
        
       $sql="SELECT   
            sum(stockledger.fulqty * TR.partperunit) + sum(stockledger.loosqty) as actual, 
            sum([BOM_items].[qty] * (case when [uom]='fulqty' then (BQ.partperunit) else 1 end)) as budget
            from stockledger
            join stockmaster TR on stockledger.itemcode=TR.itemcode 
            join [BOM_items] on stockledger.[BQitemcode]=[BOM_items].[parent_itemcode]
            join stockmaster BQ on [BOM_items].[itemcode]=BQ.itemcode 
            where stockledger.itemcode='".$stcode."' and [BOM_items].[parent_itemcode]='".$bqstcode."'";        
 
       $ResultIndex=DB_query($sql,$db);
       $rows = DB_fetch_row($ResultIndex);
       $reminder = ($rows[1] - $rows[0]);
       $KIT = PartPUNIT($stcode);
       
       if($UOM=='fulqty'){
            $ans = ($reminder/$KIT);
       } else {
            $ans = $reminder ;
       }
      
       return ($ans);
    }
      
function getBOMissued($stcode,$bqstcode,$UOM=''){
  global $db;
    $ans=0;
    
       $sql="SELECT   
            sum(stockledger.fulqty * BQ.partperunit) + sum(stockledger.loosqty)  as actual, 
            sum([BOM_items].[qty] * (case when [BOM_items].[uom]='fulqty' then (BQ.partperunit) else 1 end)) as budget
            from stockledger
            join [BOM_items] on stockledger.[BQitemcode]=[BOM_items].[parent_itemcode]
            join stockmaster BQ on [BOM_items].[itemcode]=BQ.itemcode and stockledger.itemcode=BQ.itemcode 
            where stockledger.itemcode='".$stcode."' and [BOM_items].[parent_itemcode]='".$bqstcode."'";        
 
       $ResultIndex=DB_query($sql,$db);
       $rows = DB_fetch_row($ResultIndex);
       $reminder = abs($rows[0]);
       $KIT = PartPUNIT($stcode);
       
       if($UOM=='fulqty'){
            $ans = ($reminder/$KIT);
       } else {
            $ans = $reminder ;
       }
      
          return (is_null($ans)?0:$ans);
    }
    
    
     
function getBOMTankissued($stcode,$bqstcode,$UOM=''){
 global $db;
  $ans=0;
     
       $sql="SELECT   
            sum([tanktrans].[units] * (case when [tanktrans].[uom]='fulqty' then (BQ.partperunit) else 1 end)) as actual, 
            sum([BOM_items].[qty] * (case when [BOM_items].[uom]='fulqty' then (BQ.partperunit) else 1 end)) as budget
            from tanktrans
            join [BOM_items] on tanktrans.[BQitemcode]=[BOM_items].[parent_itemcode]
            join stockmaster BQ on [BOM_items].[itemcode]=BQ.itemcode and tanktrans.itemcode=BQ.itemcode 
           where tanktrans.itemcode='".$stcode."' and [BOM_items].[parent_itemcode]='".$bqstcode."'";        
       
       $ResultIndex=DB_query($sql,$db);
       $rows = DB_fetch_row($ResultIndex);
       $reminder = abs($rows[0]);
       $KIT = PartPUNIT($stcode);
       
       if($UOM=='fulqty'){
            $ans = ($reminder/$KIT);
       } else {
            $ans = $reminder ;
       }
     
       return (is_null($ans)?0:$ans);
    }
        
    
Function PartPUNIT($stcode){
       global $db;
       
       $sql="SELECT partperunit from stockmaster where itemcode='".$stcode."' ";        
       $ResultIndex=DB_query($sql,$db);
       $rows = DB_fetch_row($ResultIndex);
       return $rows[0];
       
    }
    
?>