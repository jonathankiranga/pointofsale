<?php

function DB_AccountBalance(){
  
global $YearPeriod;
    
$procodedure ="
	SELECT 
        `ReportCode`,
        `accdesc`,
        `balance_income`,
        `ReportStyle`,
        `direct`,
        `Sale_Purchase_Neither`,
        `accno`,
case acct.`balance_income` 
     when 0 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where accountcode=acct.accno and Docdate <=(select max(`end_date`) from financialperiods  where periodno='".$YearPeriod."')) 
     when 1 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where accountcode=acct.accno and Docdate between (select min(`start_date`) from financialperiods  where periodno='".$YearPeriod."') and (select max(`end_date`) from financialperiods  where periodno='".$YearPeriod."')) 
     end as debit,
	
case acct.`balance_income` 
    when 0 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where balaccountcode=acct.accno and Docdate <=(select max(`end_date`) from financialperiods  where periodno='".$YearPeriod."'))
     when 1 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where balaccountcode=acct.accno and Docdate between (select min(`start_date`) from financialperiods  where periodno='".$YearPeriod."') and (select max(`end_date`) from financialperiods  where periodno='".$YearPeriod."')) 
     end as credit,
       
 case acct.`balance_income`
     when 0 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where accountcode=acct.accno and Docdate <=(select min(`start_date`) from financialperiods  where periodno='".$YearPeriod."')) else 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where accountcode=acct.accno and Docdate between (select min(`start_date`) from financialperiods  where  periodno='".($YearPeriod-12)."') and (select max(`end_date`) from financialperiods  where  periodno='".($YearPeriod-12)."')) 
     end as debit_Last,

case acct.`balance_income` 
    when 0 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where balaccountcode=acct.accno and Docdate <=(select min(`start_date`) from financialperiods  where periodno='".$YearPeriod."')) else 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where balaccountcode=acct.accno and Docdate between (select min(`start_date`) from financialperiods  where  periodno='".($YearPeriod-12)."') and (select max(`end_date`) from financialperiods  where  periodno='".($YearPeriod-12)."'))
    end as credit_Last,
       `Calculation`
	from acct   
	 group by 
            `ReportCode`,
            `accdesc`,
            `balance_income`,
            `ReportStyle`,
            `direct`,
            `Sale_Purchase_Neither`,
            `Calculation`,
            `accno` ;
";

 return $procodedure;

}

function DB_TBalance(){
   
global $db,$YearPeriod;
    
$procodedure ="
	SELECT 
        `ReportCode`,
        `accdesc`,
        `balance_income`,
        `ReportStyle`,
        `direct`,
        `Sale_Purchase_Neither`,
        `accno`,
case acct.`balance_income` 
     when 0 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where accountcode=acct.accno and Docdate <=(select max(`end_date`) from financialperiods  where periodno='".$YearPeriod."')) 
     when 1 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where accountcode=acct.accno and Docdate between (select min(`start_date`) from financialperiods  where periodno='".$YearPeriod."') and (select max(`end_date`) from financialperiods  where periodno='".$YearPeriod."')) 
     end as debit,
	
case acct.`balance_income` 
    when 0 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where balaccountcode=acct.accno and Docdate <=(select max(`end_date`) from financialperiods  where periodno='".$YearPeriod."'))
     when 1 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where balaccountcode=acct.accno and Docdate between (select min(`start_date`) from financialperiods  where periodno='".$YearPeriod."') and (select max(`end_date`) from financialperiods  where periodno='".$YearPeriod."')) 
     end as credit,
       
 case acct.`balance_income`
     when 0 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where accountcode=acct.accno and Docdate <=(select min([start_date]) from financialperiods  where periodno='".$YearPeriod."')) else 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where accountcode=acct.accno and Docdate between (select min(`start_date`) from financialperiods  where  periodno='".($YearPeriod-12)."') and (select max(`end_date`) from financialperiods  where  periodno='".($YearPeriod-12)."')) 
     end as debit_Last,

case acct.`balance_income` 
    when 0 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where balaccountcode=acct.accno and Docdate <=(select min(`start_date`) from financialperiods  where periodno='".$YearPeriod."')) else 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where balaccountcode=acct.accno and Docdate between (select min(`start_date`) from financialperiods  where  periodno='".($YearPeriod-12)."') and (select max(`end_date`) from financialperiods  where  periodno='".($YearPeriod-12)."'))
    end as credit_Last,
       `Calculation`
	from acct  
        where `ReportStyle`=0
         group by 
 	 `ReportCode`,
            `accdesc`,
            `balance_income`,
            `ReportStyle`,
            `direct`,
            `Sale_Purchase_Neither`,
            `Calculation`,
            `accno` ";

 return $procodedure;

}

Function DB_Profit_loss(){
    

global $db,$YearPeriod;
    
$procodedure ="SELECT 
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
          case acct.`balance_income` 
     when 0 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where accountcode=acct.accno and Docdate <=(select max(`end_date`) from financialperiods  where periodno='".$YearPeriod."')) 
     when 1 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where accountcode=acct.accno and Docdate between (select min(`start_date`) from financialperiods  where periodno='".$YearPeriod."') and (select max(`end_date`) from financialperiods  where periodno='".$YearPeriod."')) 
     end as debit,
	
case acct.`balance_income` 
    when 0 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where balaccountcode=acct.accno and Docdate <=(select max(`end_date`) from financialperiods  where periodno='".$YearPeriod."'))
     when 1 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where balaccountcode=acct.accno and Docdate between (select min(`start_date`) from financialperiods  where periodno='".$YearPeriod."') and (select max(`end_date`) from financialperiods  where periodno='".$YearPeriod."')) 
     end as credit,
       
 case acct.`balance_income`
     when 0 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where accountcode=acct.accno and Docdate <=(select min([start_date]) from financialperiods  where periodno='".$YearPeriod."')) else 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where accountcode=acct.accno and Docdate between (select min(`start_date`) from financialperiods  where  periodno='".($YearPeriod-12)."') and (select max(`end_date`) from financialperiods  where  periodno='".($YearPeriod-12)."')) 
     end as debit_Last,

case acct.`balance_income` 
    when 0 then 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where balaccountcode=acct.accno and Docdate <=(select min(`start_date`) from financialperiods  where periodno='".$YearPeriod."')) else 
	(select sum(generalledger.amount * generalledger.ExchangeRate) from generalledger where balaccountcode=acct.accno and Docdate between (select min(`start_date`) from financialperiods  where  periodno='".($YearPeriod-12)."') and (select max(`end_date`) from financialperiods  where  periodno='".($YearPeriod-12)."'))
    end as credit_Last,
 
  	[Calculation]
	from acct where [balance_income]=1
	 group by 
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]  ";



 return $procodedure;

}

Function DB_Profit_loss_by_month(){
        
global $db,$periodno;
    
$procodedure = "
	SELECT 
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno], 
        case acct.[balance_income] when 0 then 
            (select sum(generalledger.amount * generalledger.[ExchangeRate]) 
            from generalledger where accountcode=acct.accno and period <='".$periodno."')
	else 
           (select sum(generalledger.amount * generalledger.[ExchangeRate]) 
            from generalledger where accountcode=acct.accno and period='". $periodno ."') 
        end  
        as debit,

       case when acct.[balance_income]=0 then 
            (select sum(generalledger.amount * generalledger.[ExchangeRate]) 
            from generalledger where balaccountcode=acct.accno and period <='".$periodno."')
	else 
            (select sum(generalledger.amount * generalledger.[ExchangeRate]) 
            from generalledger where balaccountcode=acct.accno and period='".$periodno."') 
              
        end
        as credit,
        [Calculation]
	from acct   
	where [balance_income]=1
	group by 
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]  ";
    

 return $procodedure;
}

Function DB_Profit_loss_Project(){
    

global $db,$YearPeriod,$ProjectSelected;
    
     
$procodedure ="

        WITH RECURSIVE project_list AS (
        SELECT *, ROW_NUMBER() OVER (partition by parent order by Dimension asc , code asc) 
        AS child_nr from dimensions pc WHERE id='".$id."' and Code='".$ProjectSelected."')
        , Classwithsubclasses(sort_seq, levels, parent, code,Dimension) AS (
        SELECT CAST(CONCAT('/', LPAD(pc.child_nr, 4, '0')) AS char(4000)) AS sort_seq, 1 AS levels , pc.parent, pc.code,pc.Dimension
        FROM project_list AS pc where pc.parent is null
          UNION ALL
          SELECT CONCAT(Classwithsubclasses.sort_seq, '/', LPAD(pc.child_nr, 4, '0')) AS sort_seq , levels+1, pc.parent,pc.code ,pc.Dimension
        FROM project_list AS pc  
        INNER JOIN Classwithsubclasses ON (pc.parent = Classwithsubclasses.code)
        )
 


	SELECT 
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
            case when acct.[balance_income]=0 then 
                    (select sum(generalledger.amount * generalledger.[ExchangeRate]) 
                    from generalledger join Classwithsubclasses Pa on Pa.CODE=generalledger.[dimension2]
                    where accountcode=acct.accno and Docdate <=(select max([end_date]) 
                    from financialperiods  where periodno='".$YearPeriod."')) 
            else
                    (select  sum(generalledger.amount * generalledger.[ExchangeRate]) 
                    from generalledger  join Classwithsubclasses Pa on Pa.CODE=generalledger.[dimension2]
                    where accountcode=acct.accno  and Docdate between (select min([start_date]) 
                    from financialperiods  where periodno='".$YearPeriod."') and (select max([end_date]) "
                  . "from financialperiods  where periodno='".$YearPeriod."')) 
          
           end as debit,
	
    case when acct.[balance_income]=0 then 
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) 
                from generalledger  join Classwithsubclasses Pa on Pa.CODE=generalledger.[dimension2]
                where balaccountcode=acct.accno  and Docdate <=(select max([end_date]) 
                from financialperiods  where periodno='".$YearPeriod."'))
            else 
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger 
                join Classwithsubclasses Pa on Pa.CODE=generalledger.[dimension2]
                where balaccountcode=acct.accno  and Docdate between (select min([start_date]) 
                from financialperiods  where periodno='".$YearPeriod."') and (select max([end_date]) 
               from financialperiods  where periodno='".$YearPeriod."')) 
            
         end as credit,
       
     case when acct.[balance_income]=0 then 
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) 
                from generalledger  join Classwithsubclasses Pa on Pa.CODE=generalledger.[dimension2] 
                where accountcode=acct.accno  and Docdate <=(select min([start_date]) 
                from financialperiods  where periodno='".$YearPeriod."'))
            else 
                
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger 
                join Classwithsubclasses Pa on Pa.CODE=generalledger.[dimension2] 
                where accountcode=acct.accno and Docdate between (select min([start_date]) 
                from financialperiods  where  periodno='".($YearPeriod-12)."') and (select max([end_date]) "
              . "from financialperiods  where  periodno='".($YearPeriod-12)."')) 
             
          end as debit_Last,

	case when acct.[balance_income]=0 then 
                (select sum(generalledger.amount * generalledger.[ExchangeRate]) 
                from generalledger  join Classwithsubclasses Pa on Pa.CODE=generalledger.[dimension2] 
                where balaccountcode=acct.accno  and Docdate <=(select min([start_date]) 
                from financialperiods  where periodno='".$YearPeriod."'))
        else 
	       (select sum(generalledger.amount * generalledger.[ExchangeRate]) from generalledger 
               join Classwithsubclasses Pa on Pa.CODE=generalledger.[dimension2]
                where balaccountcode=acct.accno and Docdate between (select min([start_date]) 
                from financialperiods  where  periodno='".($YearPeriod-12)."') and (select max([end_date]) "
              . "from financialperiods  where  periodno='".($YearPeriod-12)."'))
             
           end as credit_Last,
           
  	[Calculation]
	from acct where [balance_income]=1
	 group by 
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]  
            ORDER BY 
	[ReportCode]
		
    
";



 return $procodedure;

}

function DB_FixedAssets(){
    
$procodedure="select 
        fixedassets.assetid,
        fixedassets.description,
        fixedassets.longdescription,
        fixedassets.assetcategoryid,
        fixedassets.serialno,
        (select transdate from fixedassettrans where transdate <='" . FormatDateForSQL($_POST['ProcessDate']) . "' and assetid =fixedassets.assetid limit 1) as transdate,
        (select sum(amount) from fixedassettrans where transdate <='" . FormatDateForSQL($_POST['ProcessDate']) . "'  AND fixedassettranstype='cost' and assetid =fixedassets.assetid limit 1) as [costtotal],
        (select sum(amount) from fixedassettrans where transdate <='" . FormatDateForSQL($_POST['ProcessDate']) . "' AND fixedassettranstype='depn' and assetid =fixedassets.assetid) as [depnbfwd],
        [depntype],
        [depnrate],
        (select categorydescription from [fixedassetcategories] where [categoryid]=assetcategoryid limit 1)  as categorydescription
        from fixedassets  ;";

 return $procodedure;

}

function DB_FixedAssetsRegister(){
    
global $db,$YearPeriod;

$DateFrom = FormatDateForSQL($_POST['FromDate']);
$DateTo   = FormatDateForSQL($_POST['ToDate']);
    
$procodedure="select 
        fixedassets.assetid,
        fixedassets.description,
        fixedassets.longdescription,
        fixedassets.assetcategoryid,
        fixedassets.serialno,
        (select sum(amount) from fixedassettrans where transdate <='" . $DateFrom  . "'  AND fixedassettranstype='cost' and assetid =fixedassets.assetid limit 1) as [costtotal],
        (select sum(amount) from fixedassettrans where transdate <='" . $DateFrom  . "' AND fixedassettranstype='depn' and assetid =fixedassets.assetid) as [depnbfwd],
        [depntype],
        [depnrate],
        (select sum(amount) from fixedassettrans where  fixedassettrans.transdate >='" . $DateFrom ."'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='cost' and assetid =fixedassets.assetid ) as [periodadditions],
        (select sum(amount) from fixedassettrans where  fixedassettrans.transdate >='" . $DateFrom ."'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='depn' and assetid =fixedassets.assetid ) as [perioddepn],
        (select sum(amount) from fixedassettrans where  fixedassettrans.transdate >='" . $DateFrom ."'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='disposal' and assetid =fixedassets.assetid) as [perioddisposal],
        (select sum(units) from fixedassettrans where  fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='cost' and assetid =fixedassets.assetid) as [pcs],
        (select sum(units) from fixedassettrans where  fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='Hired' and assetid =fixedassets.assetid) as [Hiredout],
        (select categorydescription from [fixedassetcategories] where [categoryid]=assetcategoryid limit 1)  as categorydescription
        from fixedassets 
      ;";

 return $procodedure;

}

function DB_AccountBudget($Yearindex){
    Global $db;
    

   $sql= "select min(financialperiods.start_date),max(financialperiods.end_date)"
           . " from financialperiods where financialperiods.periodno='".$Yearindex."' ";
   $ResultIndex = DB_query($sql,$db);
   $row = DB_fetch_row($ResultIndex);
   $start_date = $row[0]; $end_date = $row[1];
   
   $sql= "SELECT budgets.dimecode,dimensions.Dimension, sum(budgets.amount) as amount
	from dimensions 
	join budgets on dimensions.code=budgets.dimecode and budgets.periodno='".$Yearindex."' 
	join companies on dimensions.id=companies.DefaultDimension_1
	group by budgets.dimecode,dimensions.Dimension";
    $ResultIndex = DB_System($sql,$db);
    while($row = DB_fetch_array($ResultIndex)){
        $BudgetDataSet[]=array("dimecode"=>$row['dimecode'],"Dimension"=>$row['Dimension'],"amount"=>(float)$row['amount']);
    }
 
    foreach ($BudgetDataSet as $DataSet) {
        $sql= "SELECT sum(purchaseline.invoiceamount) 
            FROM [purchaseheader] join purchaseline on purchaseheader.documenttype=purchaseline.documenttype 
            and purchaseheader.documentno=purchaseline.documentno  where purchaseheader.released=1 
            and purchaseheader.documenttype=18 and (purchaseheader.Dimension_1='".$DataSet['dimecode']."')";
        $ResultIndex = DB_System($sql,$db);
        $row = DB_fetch_row($ResultIndex);
        $committed =(float) $row[0];

	$sql= "select (select sum(generalledger.amount) from generalledger where generalledger.dimension='".$DataSet['dimecode']."'
                and generalledger.Docdate between '".$start_date."' and '".$end_date."')";
        $ResultIndex = DB_System($sql,$db);
        $row = DB_fetch_row($ResultIndex);
        $expensed =(float) $row[0];

        $budgetamount =(float) $DataSet['amount'];
	$balance =(float) $budgetamount-($expensed + $committed);

	$ReportBudget[]=Array("Code"=>$DataSet['dimecode'],
            "BudgetName"=>$DataSet['Dimension'],
            "BudgetAmount"=>$budgetamount,
            "Expeses"=>$expensed,
            "Committed"=>$committed,
            "Total"=>($expensed+$committed),
            "Balance"=>$balance,
            "percent"=>(100*($balance/$budgetamount)));

    }
    return $ReportBudget;
}

?>