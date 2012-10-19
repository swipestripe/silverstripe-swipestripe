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
    	 * Billing same address checkbox, copy across shipping address and save current
    	 * billing address to revert to
    	 */
    	$('#CheckoutForm_OrderForm_BillToShippingAddress').live('click', copyAddress);
    	$('#address-shipping input[type=text], #address-shipping select').live('keyup', copyAddress);
    	$('#address-shipping input[type=text], #address-shipping select').live('blur', copyAddress);
        copyAddress();

    	function copyAddress(e) {
    		if ($('#CheckoutForm_OrderForm_BillToShippingAddress').is(':checked')) {
                $('#address-shipping input[type=text], #address-shipping select').each(function(){
                    $('#' + $(this).attr('id').replace(/Shipping/i, 'Billing'))
                        .val($('#' + $(this).attr('id')).val())
                        .parent().parent().hide();
                });
    		}
            //Only clear fields if specifically unticking checkbox
            else if ($(e.currentTarget).attr('id') == 'CheckoutForm_OrderForm_BillToShippingAddress') {
                $('#address-shipping input[type=text], #address-shipping select').each(function(){
                    $('#' + $(this).attr('id').replace(/Shipping/i, 'Billing'))
                        .val('')
                        .parent().parent().show();
                });
            }
    	}
    	
    	//Processing order indicator
    	$('#CheckoutForm_OrderForm_action_ProcessOrder').live('click', function() {
    		$('#CheckoutForm_OrderForm_action_ProcessOrder').attr('Value', 'Processing...');
    		$('.Actions .loading').show();
    	});
    })
})(jQuery);