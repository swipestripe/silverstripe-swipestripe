(function($) { 
    $(document).ready(function() { 
    	
    	var $firstSelect = $('div.OptionGroupField select').first();
    	var $lastSelect = $('div.OptionGroupField select').last();
    	var selects = new Array();
    	
    	console.log($firstSelect);
    	console.log($lastSelect);
    	
    	//This doesn't consider if there are 2 option group fields on the page
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
	    			var nextAttributeID = selects[position + 1].replace(/Form_AddToCartForm_Options-/i, '');
	    			var optionID = e.currentTarget.value;
	    			
	    			//If the next select exists, then call the AJAX to update it
	    			if ($('#'+nextID).length) {
	    				$.ajax({
	  					  url: window.location.pathname + 'options/?attributeID='+attributeID+'&optionID='+optionID+'&nextAttributeID='+nextAttributeID,
	  					  success: function(data) {
	  						  
	  						dataObj = $.parseJSON(data);
	  					    console.log(data);
	  					    
	  					    $('#Form_AddToCartForm_Options-'+dataObj.nextAttributeID).removeAttr('disabled');
	  					    $('#Form_AddToCartForm_Options-'+dataObj.nextAttributeID).html('');
	  					    
	  					    $.each(dataObj.options, function(index, val) {
	  					    	console.log('********************');
	  					    	console.log(index);
	  					    	console.log(val);
	  					    	
	  					    	$("<option value='"+index+"'>"+val+"</option>").appendTo('#Form_AddToCartForm_Options-'+dataObj.nextAttributeID);
	  					    });
	  					  }
	  					});
	    			}
	    			
	    		});
    		}
    		
    	});
    	console.log(selects);
    })
})(jQuery);