(function($) {
	$.entwine('sws', function($){

		$('.galleryfield-files').entwine({
			onmatch : function() {
				var self = this;

				this.sortable({ 
					opacity: 0.5,
					axis: 'y',
					update: function(event, ui) {

						var ids = new Array(),
								config = $.parseJSON($('div.ss-upload input').data('config').replace(/'/g,'"'));

						$('.galleryfield-files .ss-uploadfield-item').each(function(){
							ids.push($(this).attr('data-fileid'));
						});

						$.post(
							config['urlSort'], 
							{'ids' : ids}
						)
					}
				});
				
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
		});

	});
}(jQuery));
