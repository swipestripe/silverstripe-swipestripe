;(function($) {

	$.entwine('sws', function($){

		$('.attribute_option select').entwine({

			onmatch : function() {
				var self = this,
					prev = $('select[name="' + this.data('prev') + '"]');

				//If prev, prev.on change update these options
				if (prev.length) {
					prev.on('change', function(e) {
						self._updateOptions(e);
					}).change();
				}
				this._super();
			},

			onunmatch: function() {
				this._super();
			},

			_updateOptions: function(e) {
				var self = this,
					options = this.data('map')[$(e.currentTarget).val()],
					prev = $('select[name="' + this.data('prev') + '"]'),
					partial = [],
					variations = $('#ProductForm_ProductForm').data('map');

				$('option', this).remove();
				if (options !== null) {
					$.each(options, function(val, text) {

						if (prev.length) {

							// Partial variation options
							partial = [];
							prev._getOptions(partial);
							partial.push(val);

							// loop through the variations and find if match exists
							for (var key in variations) {
								if(variations.hasOwnProperty(key)){

									var variationOptions = variations[key]['options'];
									if (variationOptions.filter(function(elem) {
										return partial.indexOf(elem) > -1;
									}).length == partial.length) {
									
										//check if the option getting added does not already exist in the dropdown (to avoid duplicate options)
										if ($('option[value='+val+']',self).length == 0) {
											$("<option/>").attr("value", val).html(text).appendTo(self);
										}
									}
								}
							}
						}
						else {
							$("<option/>").attr("value", val).html(text).appendTo(self);
						}
					});
				}
				this.change();
			},

			_getOptions: function(partial) {

				var prev = $('select[name="' + this.data('prev') + '"]');
				if (prev.length) {
					prev._getOptions(partial);
				}
				var val = $(this).val();
				if (val) {
					partial.push(val);
				}
			}
		});

	});
}(jQuery));
