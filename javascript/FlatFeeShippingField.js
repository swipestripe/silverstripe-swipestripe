(function($) { 
    $(document).ready(function() { 
    	$('#CheckoutForm_OrderForm_Shipping-Country').live('change', updateOrderFormCartAJAX);
    	$('.modifier-set-field select').live('change', updateOrderFormCartAJAX);
    })
})(jQuery);