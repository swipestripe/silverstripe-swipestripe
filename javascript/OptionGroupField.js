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
    		
    		if ($self[0] != $lastSelect[0]) {
	    		$(this).change(function(e) {
	    			
	    			//Need to process an AJAX request here to get the contents of the next dropdown
	    			var position = $.inArray($(e.currentTarget).attr('id'), selects);
	    			var nextID = selects[position + 1];
	    			var attributeID = e.currentTarget.id.replace(/Form_AddToCartForm_Options-/i, '');
	    			var nextAttributeID = selects[position + 1].replace(/Form_AddToCartForm_Options-/i, '');
	    			var optionID = e.currentTarget.value;
	    			
	    			//TODO need to take the form data and figure this out properly for 3 or more options
	    			
	    			//If the next select exists, then call the AJAX to update it
	    			if ($('#'+nextID).length) {
	    				$.ajax({
	  					  url: window.location.pathname + 'options/?attributeID='+attributeID+'&optionID='+optionID+'&nextAttributeID='+nextAttributeID,
	  					  success: function(data) {
	  						  
	  						dataObj = $.parseJSON(data);
	  						
	  						if(dataObj.options) {
	  							$('#Form_AddToCartForm_Options-'+dataObj.nextAttributeID).removeAttr('disabled');
		  					    $('#Form_AddToCartForm_Options-'+dataObj.nextAttributeID).html('');
		  					    
		  					    $.each(dataObj.options, function(index, val) {
		  					    	$("<option value='"+index+"'>"+val+"</option>").appendTo('#Form_AddToCartForm_Options-'+dataObj.nextAttributeID);
		  					    });
		  					  $('#Form_AddToCartForm_Options-'+dataObj.nextAttributeID).change();
	  						}
	  					  }
	  					});
	    			}
	    			
	    		});
    		}
    	});
    	
    	//This is to trigger a change on first item, but need to trigger change on each select with one option or set empty default on those
    	$firstSelect.change();
    })
})(jQuery);