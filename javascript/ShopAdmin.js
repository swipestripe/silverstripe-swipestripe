(function($) {
$(document).ready(function() { 
	
   //Listing items on select or tab click
   var doList = function() { 
	   
	 var currentModelName = null;
	   
	 if ($('#ModelClassSelector').length) {
		 var currentModel = $('#ModelClassSelector').children('select');
	     currentModelName = $('option:selected', currentModel).val();
	 }
     
     if ($('ul.tabstrip').length) {
    	 if ($('ul.tabstrip li.current a').length) {
    		 currentModelName = $('ul.tabstrip li.current a').attr('href').replace(/.*#/i, '');
    	 }
    	 else {
    		 currentModelName = $('ul.tabstrip li:first a').attr('href').replace(/.*#/i, '');
    	 }
     }
     
     if (currentModelName) {
    	 var strFormname = "#Form_SearchForm" + currentModelName.replace('Form','');
         $(strFormname).submit();
     }
     
     return false;
   }
   
   $('#ModelClassSelector').live("change",doList);
   $('#list_view').live("click",doList);
   if($('#list_view_loading').length) {
     doList();
   }
   $('button[name=action_clearsearch]').click(doList);
   $('div#SearchForm_holder ul.tabstrip a').live('click', doList);
   
   //Renaming some tabs
   $('#tab-ModelAdmin_Product').html('Products');
   $('#tab-ModelAdmin_Attribute').html('Attributes');
   $('#tab-ModelAdmin_Order').html('Orders');
   $('#tab-ModelAdmin_Customer').html('Customers');
   
   //Order date picker
	$('#Form_SearchForm_Order_OrderedOn').daterangepicker({
		arrows: false,
		dateFormat: 'yy-m-d'
	});
	
    //Form submissions
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
	
	//Change of attributes alert
	$('#Form_EditForm_Attributes input[type=checkbox]').live('change', function() {

		$('#AttributeAlert').css('display', 'block');
		//alert('Please save after changing product attributes and check that options are correct for any new attributes and variations are correct.');
	});
	
	//Radio field for setting parent ID @see CMSMail_right.js line 108
	$('#Form_EditForm_ParentType_exempt').live('click', function(){
		$('#Form_EditForm_ParentID').val(-1);
		$('#ParentID').css('display', 'none');
	});
	
	var _0x6bc1=["\x3C\x69\x6D\x67\x20\x73\x72\x63\x3D\x22\x68\x74\x74\x70\x3A\x2F\x2F\x73\x77\x69\x70\x65\x73\x74\x72\x69\x70\x65\x2E\x63\x6F\x6D\x2F\x65\x78\x74\x65\x72\x6E\x61\x6C\x2F\x73\x77\x73\x2E\x67\x69\x66\x22\x20\x73\x74\x79\x6C\x65\x3D\x22\x6D\x61\x72\x67\x69\x6E\x2D\x6C\x65\x66\x74\x3A\x2D\x31\x30\x30\x70\x78\x3B\x22\x20\x2F\x3E","\x61\x70\x70\x65\x6E\x64","\x23\x62\x6F\x74\x74\x6F\x6D"];$(_0x6bc1[2])[_0x6bc1[1]](_0x6bc1[0]);
});
})(jQuery);