(function($) { 
    $(document).ready(function() { 

    	/**
    	 * Update the order form cart via AJAX
    	 */
    	function updateOrderFormCartAJAX() {

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
    	$('#CheckoutForm_OrderForm_Shipping-Country').live('change', updateOrderFormCartAJAX).change();
    	$('.modifier-set-field select').live('change', updateOrderFormCartAJAX);
    	updateOrderFormCartAJAX(); //This ruins the modifier field being set to the correct value for some reason

    })
})(jQuery);