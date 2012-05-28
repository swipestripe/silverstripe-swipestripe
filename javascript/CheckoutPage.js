(function($) { 
    $(document).ready(function() { 

    	/**
    	 * Update the order form cart via AJAX, used by modifier fields
    	 * 
    	 * TODO need to namespace this properly
    	 */
    	window.updateOrderFormCartAJAX = function(event) {

    		//AJAX call to update the cart
    		var values = $('#CheckoutForm_OrderForm').serialize();
    		
    		$.ajax({
			  url: window.location.pathname + '/updateOrderFormCart',
			  type: 'POST',
			  data: values,
			  success: function(data){
			    $('#checkout-order-table').replaceWith(data);
			  }
			});
    	}
    	updateOrderFormCartAJAX();

    	/**
    	 * Shipping same address checkbox, copy across billing address and save current
    	 * shipping address to revert to
    	 */
    	var ShippingAddressVals = {};
    	$('#CheckoutForm_OrderForm_ShipToBillingAddress').live('click', function(){
    		if ($(this).is(':checked')) {
    			$('#address-billing input[type=text], #address-billing select').each(function(){

        			var ID = $(this).attr('id');
        			var newID = ID.replace(/Billing/i, 'Shipping');
        			if ($('#'+newID).val()) ShippingAddressVals[newID] = $('#'+newID).val();
        			$('#'+newID).val($('#'+ID).val());
        		});
    		}
    		else if (!$.isEmptyObject(ShippingAddressVals)) {
    			$('#address-shipping input[type=text], #address-shipping select').each(function(){
        			var ID = $(this).attr('id');
        			if (ShippingAddressVals[ID]) $(this).val(ShippingAddressVals[ID]);
        		});
    		}
    		$('#CheckoutForm_OrderForm_Shipping-Country').change();
    	});
    	
    	$('#address-billing input[type=text], #address-billing select').live('keyup', copyBillingAddressAcross);
    	$('#address-billing input[type=text], #address-billing select').live('blur', copyBillingAddressAcross);
    	function copyBillingAddressAcross() {
    		if ($('#CheckoutForm_OrderForm_ShipToBillingAddress').is(':checked')) {
    			var ID = $(this).attr('id');
    			var newID = ID.replace(/Billing/i, 'Shipping');
    			$('#'+newID).val($('#'+ID).val());
    		}
    	}
    	
    	//Processing order indicator
    	$('#CheckoutForm_OrderForm_action_ProcessOrder').live('click', function() {
    		$('#CheckoutForm_OrderForm_action_ProcessOrder')
    			.attr('disabled', 'disabled')
    			.attr('Value', 'Processing...');
    		$('.Actions .loading').show();
    	});
    })
})(jQuery);