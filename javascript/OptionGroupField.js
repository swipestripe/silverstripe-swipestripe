(function($) { 
    $(document).ready(function() { 
    	
    	var $firstSelect = $('div.OptionGroupField select').first();
    	var $lastSelect = $('div.OptionGroupField select').last();
    	var selects = new Array();
    	
    	console.log($firstSelect);
    	console.log($lastSelect);
    	
    	$('div.OptionGroupField select').each(function() {

    		var $self = $(this);
    		selects.push($self.attr('id'));
    		
    		if ($self[0] != $firstSelect[0]) {
    			$(this).attr('disabled', 'disabled');
    		}
    		
    		if ($self[0] != $lastSelect[0]) {
	    		$(this).change(function(e) {
	    			
	    			//Need to process an AJAX request here to get the contents of the next dropdown
	    			var position = $.inArray($(e.currentTarget).attr('id'), selects);
	    			var nextID = selects[position + 1];
	    			var attributeID = e.currentTarget.id.replace(/Form_AddToCartForm_Options-/i, '');
	    			var optionID = e.currentTarget.value;
	    			
	    			//If the next select exists, then call the AJAX to update it
	    			if ($('#'+nextID).length) {
	    				$.ajax({
	  					  url: window.location.pathname + 'options/?attributeID='+attributeID+'&optionID='+optionID,
	  					  success: function(data) {
	  					    console.log(data);
	  					    console.log($('#'+nextID));
	  					  }
	  					});
	    			}
	    			
	    		});
    		}
    		
    	});
    	console.log(selects);
    })
})(jQuery);