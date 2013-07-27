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

	});
})(jQuery);