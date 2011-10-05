(function($) { 
    $(document).ready(function() { 
    	
    	var ShippingAddressVals = {};
    	
    	//Shipping same address checkbox, copy across billing address and save current
    	//shipping address to revert to
    	$('#CheckoutForm_OrderForm_ShipToBillingAddress').live('click', function(){
    		if ($(this).is(':checked')) {
    			$('#LeftCheckout input[type=text], #LeftCheckout select').each(function(){

        			var ID = $(this).attr('id');
        			var newID = ID.replace(/Billing/i, 'Shipping');
        			if ($('#'+newID).val()) ShippingAddressVals[newID] = $('#'+newID).val();
        			$('#'+newID).val($('#'+ID).val());
        		});
    		}
    		else if (!$.isEmptyObject(ShippingAddressVals)) {
    			$('#RightCheckout input[type=text], #RightCheckout select').each(function(){
        			var ID = $(this).attr('id');
        			if (ShippingAddressVals[ID]) $(this).val(ShippingAddressVals[ID]);
        		});
    		}
    		$('#CheckoutForm_OrderForm_Shipping-Country').change();
    	});
    	
    	$('#CheckoutForm_OrderForm_Shipping-Country').live('change', function(){
    		
    		//AJAX call to update the cart
    		var values = $('#CheckoutForm_OrderForm').serialize();
    		$.ajax({
			  url: window.location.pathname + '/updateOrderFormCart',
			  type: 'POST',
			  data: values,
			  success: function(data){
				  
			    //console.log(data);
			    
			    $('#InformationTable').replaceWith(data);
			  }
			});
    	});
    	
    	//$('#CheckoutForm_OrderForm_Shipping-Country').change();
    })
})(jQuery);