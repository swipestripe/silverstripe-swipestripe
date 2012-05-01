
<div id="ModelAdminPanel" class="ShopAdmin">

<% if EditForm %>
	$EditForm
<% else %>
  <div id="list_view_loading"><% _t('ShopAdmin_right.LOADING','Loading...') %></div>
	<form id="Form_EditForm" action="admin?executeForm=EditForm" method="post" enctype="multipart/form-data">		
	</form>
<% end_if %>

</div>

<p id="statusMessage" style="visibility:hidden"></p>
