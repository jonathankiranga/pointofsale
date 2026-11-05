<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Dimension Types');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

echo '<p class="page_title_text">'
. '<img src="css/'.$Theme.'/images/maintenance.png" title="' . _('Dimension Types') .'" alt="" />'
. ' ' . _('Dimension Types') . '</p>';

if(isset($_POST['id'])){
    $idtype=$_POST['id'];
}elseif($_GET['id']){
     $idtype=$_GET['id'];
}


if(isset($_GET['code'])){
    $editcode=$_GET['code'];
}elseif(isset($_POST['codeedit'])){
    $editcode=$_POST['codeedit'];
}


if(isset($_GET['editid'])){
    $editid=$_GET['editid'];
}elseif(isset($_POST['idedit'])){
    $editid=$_POST['idedit'];
}


if($_POST['fid']=='dty'){
    if(isset($_POST['idedit'])){
        DB_query("Update [DimensionSetUp] set Dimension_type='".$_POST['Dimension_type']."'  where id='".$_POST['idedit']."'", $db);
    }else{
         DB_query("insert into [DimensionSetUp] (Dimension_type) values ('".$_POST['Dimension_type']."')", $db);
    }
    unset($_POST);
}


if($_POST['fid']=='dval'){
    if(isset($_POST['codeedit'])){
        DB_query("Update [Dimensions] set [Dimension]='".$_POST['Dimension']."' where code='".$_POST['codeedit']."' and id='".$_POST['id']."'", $db);
    }else{
        DB_query("insert into [Dimensions] ([Dimension],[code],[id]) "
                . "values ('".$_POST['Dimension']."','".$_POST['code']."','".$_POST['id']."')", $db);
    }
     unset($_POST);
}


if(isset($idtype)){
  
$ResultIndex=DB_query("SELECT [Dimension_type] FROM [DimensionSetUp] where id='".$idtype."'", $db);
$rows=DB_fetch_row($ResultIndex);
                    
echo '<p class="page_title_text">'. ' ' .$rows[0] . '</p>';
            
echo '<form  action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<input type="hidden" name="id" value="' .$idtype. '" />';
echo '<input type="hidden" name="fid" value="dval" />';


    if(isset($editcode)){
        echo '<input type="hidden" name="codeedit" value="' .$editcode. '" />';
        $ResultIndex=DB_query("SELECT [code],[Dimension] FROM [Dimensions] where id='".$idtype."' and code='".$editcode."'", $db);
        $rows=DB_fetch_row($ResultIndex);
            $_POST['code']=$rows[0];
            $_POST['Dimension']=$rows[1];

    }

    echo '<table><tr>'
        . '<td>Code :</td><td><input type="text" name="code" value="'.$_POST['code'].'" required="required"/></td></tr>'
        . '<tr><td>Dimension :</td><td><input type="text" name="Dimension" value="'.$_POST['Dimension'].'" required="required"/></td>'
        . '</tr>'
        . '</table><div><input type="submit" name="submit" value="Update"/></div>';
    echo '</div></form>';


    $ResultIndex=DB_query("SELECT [code],[Dimension] FROM [Dimensions] where id='".$idtype."'", $db);
     echo '<table><tr><th>Dimension Values</th></tr>';
    while($rows=DB_fetch_array($ResultIndex)){
       echo '<tr><td><a href="'.$_SERVER['PHP_SELF'].'?code='.$rows['code'].'&id='.$idtype.'">'.$rows['Dimension'].'</a></td></tr>';
    }
    
    echo '</table>';
    
}elseif(isset($_GET['editid'])) {
       
    echo '<form  action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><div>';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
 echo '<input type="hidden" name="fid" value="dty" />';
    if(isset($editid)){
        
        echo '<input type="hidden" name="idedit" value="' .$editid. '" />';

        $ResultIndex=DB_query("SELECT [id],[Dimension_type] FROM [DimensionSetUp] where id='".$editid."'", $db);
        $rows=DB_fetch_row($ResultIndex);
            $_POST['id']=$rows[0];
            $_POST['Dimension_type']=$rows[1];

    }

    echo '<table><tr>'
        . '<td>Dimension Type:</td><td><input type="text" name="Dimension_type" value="'.$_POST['Dimension_type'].'" required="required"/></td>'
        . '</tr>'
        . '</table><div><input type="submit" name="submit" value="Update"/></div>';
    echo '</div></form>';
    
}else{
    
    $ResultIndex=DB_query('SELECT [id],[Dimension_type] FROM [DimensionSetUp]', $db);
    
    echo '<table class="table table-bordered"><tr><th>Dimension Type</th></tr>';
    while($rows=DB_fetch_array($ResultIndex)){
       echo '<tr><td><a href="'.$_SERVER['PHP_SELF'].'?id='.$rows['id'].'"> Click to Add :'.$rows['Dimension_type'].'</a></td>'
          . '<td><a href="'.$_SERVER['PHP_SELF'].'?editid='.$rows['id'].'"> Edit TYPE</a></td></tr>';
    }
    
    echo '</table>';
       
    echo '<form  action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><div>';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
     echo '<input type="hidden" name="fid" value="dty" />';
    echo '<table class="table table-bordered"><tr>'
        . '<td>Dimension Type:</td><td><input type="text" name="Dimension_type"  required="required"/></td>'
        . '</tr><tr><td colspan="2"><div><input type="submit" name="submit" value="Create Dimension Type"/></div></td></tr>'
        . '</table>';
    echo '</div></form>';
}

include('includes/footer.inc');
?>