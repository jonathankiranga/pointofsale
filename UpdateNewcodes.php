<?php
$PageSecurity=0;
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');

$param2="select `sampletypeid`,`ParameterID` from `laboratorystandards` where CHAR_LENGTH(`ParameterID`)  <4";
$ResultIndex=DB_query($param2,$db);
while($row= DB_fetch_array($ResultIndex)){
    $laboratorystandards[]=$row;
}

$updatestatement=array();
foreach ($laboratorystandards as $row) {
    $sampleID=trim($row['sampletypeid']);
    $paramtypeid=trim($row['ParameterID']);
    $padded=padR($paramtypeid);
    
    $updatestatement[]=sprintf("UPDATE `laboratorystandards` set `ParameterID`='%s'  WHERE"
        . " `sampletypeid`='%s' and `ParameterID`='%s' and CHAR_LENGTH(`ParameterID`)<4",$padded,$sampleID,$paramtypeid);

    $updatestatement[]=sprintf("update `testsvalidation` set `test_type_id`='%s' where "
            . "`sampletype_id`='%s' and `test_type_id`='%s' and CHAR_LENGTH(`test_type_id`)<4",$padded,$sampleID,$paramtypeid);
    
    $updatestatement[]=sprintf("UPDATE `LabPostingDetail` set `ParameterID`='%s'  WHERE"
        . " `SampleTypeID`='%s' and `ParameterID`='%s' and CHAR_LENGTH(`ParameterID`)<4",$padded,$sampleID,$paramtypeid);
    
 
}

foreach ($updatestatement as  $value) {
    echo "$value".'\n\r';
    $ResultIndex=DB_query($value,$db);
}