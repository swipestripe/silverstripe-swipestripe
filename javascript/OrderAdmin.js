(function($) { 
    $(document).ready(function() { 
    	
    	//Date picker
    	$('#Form_SearchForm_Order_OrderedOn').daterangepicker({
    		arrows: false,
    		dateFormat: 'yy-m-d'
    	});
    	
       //Submit the search form
       var doList = function() {
	     $("#Form_SearchForm_Order").submit();
	     return false;
	   };
       doList();
     
       //Submit custom model admin actions 
       $('#right input:submit').unbind('click').live('click', function(){
            var form = $('#right form');
            var formAction = form.attr('action') + '?' + $(this).fieldSerialize();
            if(typeof tinyMCE != 'undefined') tinyMCE.triggerSave();
             
            $.ajax({
                url : formAction,
                data : form.formToArray(),
                dataType : "json",
                success : function(json) {
                    tinymce_removeAll();
         
                    $('#right #ModelAdminPanel').html(json.html);
                    if($('#right #ModelAdminPanel form').hasClass('validationerror')) {
                        statusMessage(ss.i18n._t('ModelAdmin.VALIDATIONERROR', 'Validation Error'), 'bad');
                    } else {
                        statusMessage(json.message, 'good');
                    }
         
                    Behaviour.apply();
                    if(window.onresize) window.onresize();
                }
            });
            return false;
        });
       
       //Work flow tab
       //Keep radio buttons and drop down in sync
       $('input[name="status"]').live('change', function() {
    	   $('#Form_EditForm_Status').val($(this).val());
       });
       
       $('#Form_EditForm_Status').live('change', function() {
    	   var self = this;
    	   
    	   console.log('there was a change in the matrix');
    	   
    	   $('input[name="status"]').each(function() {
    		   if ($(this).val() == $(self).val()) {
    			   $(this).attr('checked', 'checked');
    		   }
    		   else {
    			   $(this).removeAttr('checked');
    		   }
    	   });
       });
       
    })
})(jQuery);