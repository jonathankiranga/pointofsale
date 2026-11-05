<?php

$Dimesion_one=array();
$Dimesion_two=array();

$Result = DB_query("SELECT "
        . "[Dim1].[Dimension_type] as D1,"
        . "[Dim2].[Dimension_type] as D2,"
        . "[DefaultDimension_1] ,"
        . "[DefaultDimension_2]  "
        . "FROM [companies]  "
        . "left join DimensionSetUp Dim1 on [companies].[DefaultDimension_1]=Dim1.[id] "
        . "left join DimensionSetUp Dim2 on [companies].[DefaultDimension_2]=Dim2.[id] "
        . "where [companies].[coycode]=1",$db);
$DimensionRow = DB_fetch_row($Result);

$id1 = $DimensionRow[2];
$id2 = $DimensionRow[3];
$_SESSION['Dimesion_one_name']= (mb_strlen($DimensionRow[0])>0)?$DimensionRow[0]:'Class';
$_SESSION['Dimesion_two_name']= (mb_strlen($DimensionRow[1])>0)?$DimensionRow[1]:'Project';

///////////////////////////////////////////////////////////////////////
$DRows = DB_query("SELECT  ltrim(rtrim([Code])) as Code,[Dimension] FROM [Dimensions] where [id]='".$id1."' order by Dimension", $db);
while($row = DB_fetch_array($DRows)){
    $Dimesion_one[$row['Code']]=$row['Dimension'];
}

$_SESSION['SelectObject']['dimensionone']='<td>'.$_SESSION['Dimesion_one_name'].':</td>'
. '<td><Select name="DimensionOne"><option value=""></option>'; 
foreach ($Dimesion_one as $key=> $value) {
  $_SESSION['SelectObject']['dimensionone'] .= '<option value="'.$key.'" '.($key==$_POST['DimensionOne']?'selected="selected"':'').'>'.$value.'</option>';
}
$_SESSION['SelectObject']['dimensionone'] .='</select></td>';
  
 
////////////////////////////////////////////////////////////
$DRows2 = DB_query("SELECT  ltrim(rtrim([Code])) as Code,[Dimension] FROM [Dimensions] where [id]='".$id2."'", $db);
while($row = DB_fetch_array($DRows2)){
   $Dimesion_two[$row['Code']]=$row['Dimension'];
}

$_SESSION['SelectObject']['dimensiontwo']='<td>'.$_SESSION['Dimesion_two_name'].':</td>'
. '<td><Select name="DimensionTwo"><option value=""></option>'; 
foreach ($Dimesion_two as $key => $value) {
    $_SESSION['SelectObject']['dimensiontwo'] .= '<option value="'.$key.'" '.($key==$_POST['DimensionTwo']?'selected="selected"':'').'>'.$value.'</option>';
}
$_SESSION['SelectObject']['dimensiontwo'] .='</select></td>';

 
/* 
 * 
 * this is a global array that can be accessed throught the application
 */

 $ResultIndex = DB_query("SELECT 
       [sampletypeid] ,[ParameterID] ,[Parameter]
      ,[Limits],[Method],[Units] ,[SalesPrice],[vital] ,[category]
      ,[NoStandardlimit] FROM [LaboratoryStandards]",$db);
while($rowdate = DB_fetch_array($ResultIndex)){
    $id=trim($rowdate['ParameterID']);
    $SampID=trim($rowdate['sampletypeid']);
    $_SESSION['_LaboratoryStandards'][$SampID][$id]= $rowdate;
}

$ResultIndex = DB_query('SELECT [sampletypeid] ,[salesgroup],[sampletypeName]
      ,[SalesPrice] FROM [SampleTypes]',$db);
while($rowdate = DB_fetch_array($ResultIndex)){
    $id=trim($rowdate['sampletypeid']);
    $_SESSION['_SampleTypes'][$id]= $rowdate;
}


$ResultIndex = DB_query("SELECT [vatc],[vat],[vatdecrip],[taxcatid],[code] FROM [vatcategory] 
  join [inventorypostinggroup] on [vatcategory]=[vatcategory].vatc",$db);
while($rowdate = DB_fetch_array($ResultIndex)){
    $id=trim($rowdate['code']);
    $_SESSION['_inventorypostinggroup'][$id]= $rowdate;
}
/*Server=localhost\SQLEXPRESS;Database=master;Trusted_Connection=True;*/