;(function($) { 
	$.entwine('sws', function($){

		$('.order-form').entwine({

			onmatch : function() {
				var self = this;

				this.updateCart();
				this.on('submit', function(e){
					self._indicateProcessing(e);
				});

				this._super();
			},

			onunmatch: function() {
				this._super();
			},

			updateCart: function() {
				var self = this;

				var values = this.serialize();
    		
    		$.ajax({
				  url: window.location.pathname + '/OrderForm/update',
				  type: 'POST',
				  data: values,
				  beforeSend: function() {
				  	$('#cart-loading-js').show();
				  	$('#checkout-order-table').addClass('loading-currently');
				  },
				  success: function(data){
				    $('#checkout-order-table').replaceWith(data);
				  },
				  complete: function() {
				  	$('#cart-loading-js').hide();
				  	$('#checkout-order-table').removeClass('loading-currently');
				  }
				});
			},

			_indicateProcessing: function(e) {

				$('input[name="action_process"]', this).attr('value', 'Processing...');
    		$('.Actions .loading', this).show();
			}
		});

		$('input.shipping-same-address').entwine({

			onmatch : function() {
				var self = this;
				var form = this.closest('form');

				console.log(this);

				this._copyAddress();

				this.on('click', function(e) {
					self._copyAddress(e);
				});
				
				$('#address-shipping input[type=text], #address-shipping select', form).on('keyup blur', function(e){
					self._copyAddress(e);
				});

				this._super();
			},

			onunmatch: function() {
				this._super();
			},
			
			_copyAddress: function(e) {
				var form = this.closest('form');

				if (this.is(':checked')) {
	        $('#address-shipping input[type=text], #address-shipping select', form).each(function(){
            $('#' + $(this).attr('id').replace(/Shipping/i, 'Billing'))
	            .val($('#' + $(this).attr('id')).val())
	            .parent().parent().hide();
	        });
    		}
    		//Only clear fields if specifically unticking checkbox
        else if ($(e.currentTarget).attr('id') == this.attr('id')) {
          $('#address-shipping input[type=text], #address-shipping select', form).each(function(){
            $('#' + $(this).attr('id').replace(/Shipping/i, 'Billing'))
              .val('')
              .parent().parent().show();
          });
        }
			}
		});

	});
})(jQuery);