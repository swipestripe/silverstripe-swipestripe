(function($) { 
    $(document).ready(function() { 

    	/**
    	 * Shipping same address checkbox, copy across billing address and save current
    	 * shipping address to revert to
    	 */
    	var ShippingAddressVals = {};
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
    	
    	$('#LeftCheckout input[type=text], #LeftCheckout select').live('keyup', copyBillingAddressAcross);
    	$('#LeftCheckout input[type=text], #LeftCheckout select').live('blur', copyBillingAddressAcross);
    	function copyBillingAddressAcross() {
    		if ($('#CheckoutForm_OrderForm_ShipToBillingAddress').is(':checked')) {
    			var ID = $(this).attr('id');
    			var newID = ID.replace(/Billing/i, 'Shipping');
    			$('#'+newID).val($('#'+ID).val());
    		}
    	}
    })
})(jQuery);