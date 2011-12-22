(function($) { 
    $(document).ready(function() { 
    	$('#StockChoice input[name=StockChoice]').live('change', function(){

    		var radioVal = $('input[name=StockChoice]:checked', '#StockChoice').val();
    		if (radioVal == 0) {
    			$('input[name=Stock]').addClass('HiddenStock');
    			$('input[name=Stock]').val('-1');
    		}
    		else
    		if (radioVal == 1) {
    			$('input[name=Stock]').removeClass('HiddenStock');
    			$('input[name=Stock]').val('0');
    		}
    	});
    })
})(jQuery);