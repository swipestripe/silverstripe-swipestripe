;(function($) {
	$(document).ready(function() { 

		$('.galleryfield-files').sortable({ 
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
	});
}(jQuery));
