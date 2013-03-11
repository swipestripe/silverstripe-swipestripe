;(function($) {
	$.entwine('sws', function($){

		$('.cart-form .remove-item-js').entwine({

			onmatch : function() {
				var self = this;

				this.on('click', function(e) {
					self._removeItem(e);
				});

				this._super();
			},

			onunmatch: function() {
				this._super();
			},
			
			_removeItem: function(e) {
				e.preventDefault();
				var form = this.closest('form');

				//Set quantity for this item to 0 and update the form
				$('input[name="Quantity\\[' + this.data('item') + '\\]"]', form).val(0);
				$('input[name="action_updateCart"]', form).click();
			}
		});

	});
})(jQuery);
