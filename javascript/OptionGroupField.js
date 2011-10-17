(function($) { 
    $(document).ready(function() { 
    	
    	var $firstSelect = $('div.OptionGroupField select').first();
    	var $lastSelect = $('div.OptionGroupField select').last();
    	var selects = new Array();
    	
    	//TODO This doesn't consider if there are 2 option group fields on the page
    	$('div.OptionGroupField select').each(function() {

    		var $self = $(this);
    		selects.push($self.attr('id'));
    		
    		if ($self[0] != $firstSelect[0]) {
    			$(this).attr('disabled', 'disabled');
    		}
    		
    		//Get options for the next attribute dropdown on the page via AJAX
    		if ($self[0] != $lastSelect[0]) {
    			
	    		$(this).change(function(e) {
	    			
	    			//Need to process an AJAX request here to get the contents of the next dropdown
	    			var position = $.inArray($(e.currentTarget).attr('id'), selects);
	    			var nextID = selects[position + 1];
	    			
	    			//Disable the select inputs 'underneath' the one we are changing
	    			//so that their values are not sent via AJAX
	    			for (var i = position + 1, len = selects.length; i < len; i++) {
	    				$('#'+selects[i]).attr('disabled', 'disabled');
	    			}
	    			
	    			var attributeID = e.currentTarget.id.replace(/Form_AddToCartForm_Options-/i, '');
	    			var optionID = e.currentTarget.value;

	    			
	    			//If the next select exists, then call the AJAX to update it
	    			if ($('#'+nextID).length) {
	    				
	    				var values = $('#Form_AddToCartForm').serialize();
	    				var nextAttributeID = selects[position + 1].replace(/Form_AddToCartForm_Options-/i, '');
	    				values += '&NextAttributeID='+nextAttributeID;
	    				
	    				$.ajax({
	  					  url: window.location.pathname + 'options/',
	  					  type: 'POST',
		  				  data: values,
	  					  success: function(data) {
	  						  
	  						dataObj = $.parseJSON(data);
	  						
	  						if(dataObj.options) {
	  							$('#Form_AddToCartForm_Options-'+dataObj.nextAttributeID).removeAttr('disabled');
		  					    $('#Form_AddToCartForm_Options-'+dataObj.nextAttributeID).html('');
		  					    
		  					    $.each(dataObj.options, function(index, val) {
		  					    	$("<option value='"+index+"'>"+val+"</option>").appendTo('#Form_AddToCartForm_Options-'+dataObj.nextAttributeID);
		  					    });
		  					  
		  					    //if (dataObj.count == 1) {
		  						  $('#Form_AddToCartForm_Options-'+dataObj.nextAttributeID).change();
		  					    //}
	  						}
	  					  }
	  					});
	    			}
	    			
	    		});
    		}
    		
    		//Get price difference for the current variation selected
    		$(this).change(function(e) {
    			
    			var values = $('#Form_AddToCartForm').serialize();
    			
    			$.ajax({
				  url: window.location.pathname + 'variationprice/',
				  type: 'POST',
  				  data: values,
				  success: function(data) {
					  
					dataObj = $.parseJSON(data);
					
					if (dataObj.priceDifference && $('#VariationPrice').length) {
						$('#VariationPrice').html(dataObj.priceDifference);
					}
					else if ($('#VariationPrice').length) {
						$('#VariationPrice').html('');
					}
				  }
				});
    		});
    	});
    	
    	//This is to trigger a change on first item
    	$firstSelect.change();
    	
    })
})(jQuery);