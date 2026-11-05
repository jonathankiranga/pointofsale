<?php

function CalculateAveCost($docno){
    Global $db;
    
        $SQL="IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[PurchaseAverageStockValue]') AND type in (N'P', N'PC'))
        DROP PROCEDURE [PurchaseAverageStockValue]";
    $r=DB_query($SQL,$db);

  
$cmdsql= "Create PROCEDURE [PurchaseAverageStockValue]  AS
BEGIN

	Declare @UnitPrice decimal(18,2),@Quantity decimal(18,2),@itemcode varchar(20)
	Declare @AveCost decimal(18,2),@StockBalance decimal(18,2)

	DECLARE PurchaseLine_cursor CURSOR FOR	select 
	stockmaster.itemcode ,
	PurchaseLine.[Quantity] ,
	(PurchaseLine.UnitPrice * currencies.rate) as UnitPrice,
	IFNULL(stockmaster.averagestock,0) as AveCost,
	IFNULL((sum(stockledger.fulqty) * stockmaster.partperunit),0) + IFNULL(sum(stockledger.loosqty),0) as StockBalance
	 from PurchaseLine 
         join stockmaster on stockmaster.itemcode=PurchaseLine.code  
	 join stockledger on stockmaster.itemcode=stockledger.itemcode
	 join PurchaseHeader on PurchaseLine.documentno=PurchaseHeader.documentno
	 join currencies on PurchaseHeader.currencycode=currencies.currabrev
	 where PurchaseLine.documentno='".$docno."'
	 group by 
	 stockmaster.itemcode,
	 stockmaster.averagestock,
	 PurchaseLine.UOM,
	 PurchaseLine.UnitPrice,
	 PurchaseLine.[Quantity],
	 currencies.rate,
	 stockmaster.partperunit
	
	OPEN  PurchaseLine_cursor
	FETCH NEXT FROM  PurchaseLine_cursor into @itemcode,@Quantity,@UnitPrice,@AveCost,@StockBalance
	WHILE (@@FETCH_STATUS = 0)
	BEGIN

	   Update stockmaster 
           set averagestock =((@Quantity * @UnitPrice)+(@AveCost * @StockBalance))/(@StockBalance+@Quantity)  where itemcode=@itemcode

	   FETCH NEXT FROM  PurchaseLine_cursor into @itemcode,@Quantity,@UnitPrice,@AveCost,@StockBalance
	END
	CLOSE  PurchaseLine_cursor
	DEALLOCATE  PurchaseLine_cursor


END
";

  $r=DB_query($cmdsql,$db);


}




?>