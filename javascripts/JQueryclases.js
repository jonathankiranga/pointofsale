$(document).on({
    ajaxStart: function ()
    {
        $("body").addClass("loading");
    },
    ajaxStop: function ()
    {
         $("body").removeClass("loading");
    }
});


$(document).ready(function() {
    
      $("#noofquotes").click(function (){
            $.post("Ajax/QuoteslinesAjax.php",{
               noofquotes: $("#numberoflines").val()
            }, function (data){
                    $("#samplehtml").empty();
                    $("#samplehtml").append(data);
                    $("#labform").submit();
            });
	});
        
        $("#noofsample").click(function (){
            $.post("Ajax/SampleregAjax.php",{
               numberofsamples: $("#numberofsamples").val()
            }, function (data){
                    $("#samplehtml").empty();
                    $("#samplehtml").append(data);
                    $("#labform").submit();
            });
	});
        
         $.fn.updatediscount = function(){ 
           var rate  = $("#Qdiscount").val() ;
           var inv   = $("#totalinvoiceamount").val() ;
           var vat   = $("#totalvatamount").val() ;
           var vatdiscount = (parseVals(rate) * 0.01) * parseVals(vat);
           var netdiscount = (parseVals(rate) * 0.01) * parseVals(inv);
           $("#Qvatamount").val(parseVals(vatdiscount)) ;
           $("#Qinvoiceamount").val(parseVals(netdiscount)) ;
           $("#Linvoiceamount").val(parseVals(inv) - parseVals(netdiscount)) ;
           $("#Lvatamount").val(parseVals(vat) - parseVals(vatdiscount));
           
          }
       
      $.fn.updatediscount();
  
      function parseVals(val) {
            return (val != '') ? parseInt(val) : 0;
        }
            
       $.fn.quotesampletype = function(thisobject){ 
           var elmId = thisobject.attr("id");
           var length = elmId.length;
           var subStr = elmId.substring(4,length);
           $.post("Ajax/QuoteslinesAjax.php",{
                    Pricingid: $("#PRC_"+subStr).val(),
                    Labid: subStr,
                    reloadFLEX: 'YES',
                    sampletypeid: $("#"+elmId).val() 
            }, function (data){
                    $("#FLEX_"+subStr).empty();
                    $("#FLEX_"+subStr).append(data);
            });
        }
        
        $.fn.quotePricing = function(thisobject){ 
           var elmId = thisobject.attr("id");
           var length = elmId.length;
            var subStr = elmId.substring(4,length);
           $.post("Ajax/QuoteslinesAjax.php",{
                    Pricingid: $("#"+elmId).val(),
                    Labid: subStr,
                    reloadFLEX: 'YES',
                    sampletypeid: $("#SAM_"+subStr).val()
            }, function (data){
                    $("#FLEX_"+subStr).empty();
                    $("#FLEX_"+subStr).append(data);
            });
        }
        
       $.fn.sampletype = function(thisobject){ 
           var elmId = thisobject.attr("id");
           var length = elmId.length;
           var subStr = elmId.substring(4,length);
           $.post("Ajax/SampleregAjax.php",{
                    Pricingid: $("#PRC_"+subStr).val(),
                    Labid: subStr,
                    reloadFLEX: 'YES',
                    sampletypeid: $("#"+elmId).val() 
            }, function (data){
                    $("#FLEX_"+subStr).empty();
                    $("#FLEX_"+subStr).append(data);
            });
        }

        $.fn.Pricing = function(thisobject){ 
           var elmId = thisobject.attr("id");
           var length = elmId.length;
            var subStr = elmId.substring(4,length);
           $.post("Ajax/SampleregAjax.php",{
                    Pricingid: $("#"+elmId).val(),
                    Labid: subStr,
                    reloadFLEX: 'YES',
                    sampletypeid: $("#SAM_"+subStr).val()
            }, function (data){
                    $("#FLEX_"+subStr).empty();
                    $("#FLEX_"+subStr).append(data);
            });
        }

	$("#selectjournaltoedit").click(function ()
	{
		$.post("includes/JournalAjax.php",
		{
			journaldate: $("#date").val(),
			journalfind: $("#DocumentNO").val()
		}, function (data)
		{       $("#journalspan").empty();
			$("#journalspan").append(data);
		});
	});
        

        
	$("#GetBankData").click(function ()
	{
		$.post("includes/bankreconciliationAJAX.php",
		{
			Bank_Code: $("#bankselected").val(),
			statementstartdate: $("#statementstartdate").val(),
			enddate: $("#bankrecondate").val()
		}, function (data)
		{
			$("#filtereddata").remove();
			$("#loadbankdata").append(data);
		});
	});

    $("#searchcustomer").click(function(){
        $.post("Ajax/ConnectAjax.php",{
          offset: $("#searchcustomer").offset(),
          height: $("#searchcustomer").height() * 0.5,
          Customerfind: $("#CustomerName").val()
         },function(data){
           $("#findcustomer").remove();
           $("body").append(data);           
        });
    });
     
    $("#searchvendor").click(function(){
        $.post("Ajax/ConnectAjax.php",{
          offset: $("#searchvendor").offset(),
          height: $("#searchvendor").height() * 0.5,
          Vendorfind: $("#VendorName").val()
         },function(data){
           $("#findVendor").remove();
           $("body").append(data);           
        });
    });
     
    $("#searchstock").click(function(){
        $.post("Ajax/ConnectAjax.php",{
          offset: $("#searchstock").offset(),
          height: $("#searchstock").height() * 0.5,
          Stockfind: $("#stockname").val()
         },function(data){
           $("#findStock").remove();
           $("body").append(data);           
        });
    });
      
    $("#searchjobs").click(function(){
        $.post("Ajax/ConnectAjax.php",{
          offset: $("#searchjobs").offset(),
          height: $("#searchjobs").height() * 0.5,
          Jobfind: $("#stockname").val()
         },function(data){
           $("#findJob").remove();
           $("body").append(data);           
        });
    });
       
    $("#searchfixedassets").click(function(){
        $.post("Ajax/ConnectAjax.php",{
          offset: $("#searchfixedassets").offset(),
          height: $("#searchfixedassets").height() * 0.5,
          Assetfind: $("#stockname").val()
         },function(data){
           $("#findStock").remove();
           $("body").append(data);           
        });
    });
       
       
    $("#searchsampletypes").click(function(){
        $.post("Ajax/ConnectAjax.php",{
          offset: $("#searchsampletypes").offset(),
          height: $("#searchsampletypes").height() * 0.5,
          Samplefind: $("#stockname").val()
         },function(data){
           $("#findStock").remove();
           $("body").append(data);           
        });
    });   
       
      $("#stocktransferitemcode").click(function(){
        $.post("Ajax/ConnectAjax.php",{
          stocktransferitemcode: $("#stocktransferitemcode").val()
         },function(data){
           $("#UOMstocktransfer option").remove();
           $("#UOMstocktransfer").append(data); 
       });
    });
     
    $("#searchemployee").click(function(){
        $.post("Ajax/ConnectAjax.php",{
          offset: $("#searchemployee").offset(),
          height: $("#searchemployee").height() * 0.5,
          EmployeeNamefind: $("#EmployeeName").val()
         },function(data){
           $("#EmployeeNamefind").remove();
           $("body").append(data);           
        });
    });
    

$("#searchchart2").click(function (){
      $.post("Ajax/ConnectAjax.php",
      {
          offset: $("#searchchart2").offset(),
          height: $(window).scrollTop(),
          Chartfind: 'reload'
      }, function (data)
      {
          $("#findSchart").remove();
          $("body").append(data);
      });
  });
        
        
  $("a, button, input , select , submit").click(function(){
     sessionStorage.scrolly= $(window).scrollTop();
  });
  
  if (sessionStorage.scrolly) {
     $(window).scrollTop(sessionStorage.scrolly);
    sessionStorage.clear();
  }
  
} );
