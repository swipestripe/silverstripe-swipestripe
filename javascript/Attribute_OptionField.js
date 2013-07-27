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

				$('option', this).remove();
				if (options != null) {
					$.each(options, function(val, text) {
						self.append(new Option(text, val));
					});
				}
				this.change();
			}
		});

	});
}(jQuery));
