;(function($) {
	$.entwine('sws', function($){

		$('.product-form').entwine({

			onmatch : function() {
				var self = this;

				this.find('.attribute_option select').on('change', function(e) {
					self._updatePrice(e);
				});
				self._updatePrice();

				this._super();
			},

			onunmatch: function() {
				this._super();
			},
			
			_updatePrice: function(e) {
				var self = this;
				var form = this.closest('form');

				//Get selected options
				var options = [];
				$('.attribute_option select', form).each(function(){
					options.push($(this).val());
				});

				//Find the matching variation
				var variations = form.data('map');
				for (var i = 0; i < variations.length; i++){

					var variationOptions = variations[i]['options'];

					//If options arrays match update price
					if ($(variationOptions).not(options).length == 0 && $(options).not(variationOptions).length == 0) {
						$(this).parents('.product.sws').find('.product-price-js').html(variations[i]['price']);
					}
				}
			}
		});

	});
}(jQuery));
