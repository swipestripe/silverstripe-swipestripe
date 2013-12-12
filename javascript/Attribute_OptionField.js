;(function($) {
	$.entwine('sws', function($){

		$('.attribute_option select').entwine({

			onmatch : function() {
				var self = this;

				//If prev, prev.on change update these options
				var prev = $('select[name="' + this.data('prev') + '"]');

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
				var self = this;
				var options = this.data('map')[$(e.currentTarget).val()];
				var superOption = $('.attribute_option select:eq(0)').val();

				var form = this.closest('form');
				var variations = form.data('map');

				$('option', this).remove();
				if (options != null) {
					$.each(options, function(val, text) {
						var add = false;
						for (var i = 0; i < variations.length; i++){
							var variationOptions = variations[i]['options'];
							if($.inArray(superOption, variationOptions) > -1 && $.inArray(val, variationOptions) > -1) {
								var add = true;
							}
						}

						if(add) {
							$("<option/>").attr("value", val).html(text).appendTo(self);
						}
					});
				}
				this.change();
			}
		});

	});
}(jQuery));
