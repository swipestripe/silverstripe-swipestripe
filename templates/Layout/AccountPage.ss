<div class="account-page sws">
	<h2>$Title</h2>
	
	<p>
	Hi <strong>$Customer.FirstName</strong>
	</p>
	
	$Content
	
	<% if Orders %>
	  <table class="table table-striped table-bordered">
	    <thead>
	      <tr>
	        <th>#</th>
	        <th><% _t('AccountPage.DATE','Date') %></th>
          <th><% _t('AccountPage.SHIP_TO','Ship to') %></th>
          <th><% _t('AccountPage.TOTAL','Total') %></th>
          <th><% _t('AccountPage.STATUS','Status') %></th>
	        <th>&nbsp;</th>
	      </tr>
	    </thead>
	    <tbody>
	      <% control Orders %>
	      <tr>
	        <td>$ID</td>
	        <td>$OrderedOn.Format(j M y)</td>
	        <td>
	          <% control ShippingAddress %>
			        <% if Address %>      $Address<br />      <% end_if %>
			        <% if AddressLine2 %> $AddressLine2<br /> <% end_if %>
			        <% if City %>         $City<br />         <% end_if %>
			        <% if PostalCode %>   $PostalCode<br />   <% end_if %>
			        <% if State %>        $State<br />        <% end_if %>
			        <% if Country %>      $Country<br />      <% end_if %>
			      <% end_control %>
	        </td>
	        <td>$Total.Nice</td>
	        <td>$Status ($PaymentStatus)</td>
	        <td><a href="$Link"><% _t('AccountPage.VIEW_THIS_ORDER','View this order') %></a></td>
	      </tr>
	      <% end_control %>
	    </tbody>
	  </table>
	<% else %> 
	  <div class="alert alert-info">
      <% _t('AccountPage.NO_ORDERS','You do not currently have any orders. In future you will be able to view your recent orders from here.') %>
    </div>
	<% end_if %>
</div>