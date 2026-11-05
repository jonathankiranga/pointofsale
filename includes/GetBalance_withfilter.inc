<?php

function db_GetBalance_withfilter($prd,$accno){

$sql="SELECT 
        ifnull([ReportCode],''),
        [accdesc],
        ifnull([balance_income],''),
        ifnull([ReportStyle],''),
        [direct],
        ifnull([Sale_Purchase_Neither],''),
        [accno],
   case  when (acct.[balance_income]=0) then 
	(select sum(generalledger.amount * generalledger.[ExchangeRate]) 
        from generalledger where accountcode='".$accno."' and [period] <'".$prd."') else 
	( 0) end as debit,
   case  when (acct.[balance_income]=0) then 
	(select sum(generalledger.amount * generalledger.[ExchangeRate]) 
        from generalledger where balaccountcode='".$accno."' and [period] <'".$prd."') else 
	( 0) end as credit,
 [Calculation]
	from acct where [accno]='".$accno."' 
	 group by 
            `ReportCode`,
            `accdesc`,
            `balance_income`,
            `ReportStyle`,
            `direct`,
            `Sale_Purchase_Neither`,
            `Calculation`,
            `accno` 
         ORDER BY [ReportCode] asc ";
 
return $sql;
};

?>