<div class="sws">

	<table class="table table-bordered">
	  <tr>
	    <th><% _t('OrderAdmin.ORDER','Order') %> #$ID - $Status</th>
	  </tr>
	  <tr>
	    <td>
	      $OrderedOn.Format(j M Y - g:i a)<br />
	      ($PaymentStatus)
	    </td>
	  </tr>
	</table>
	
	<% include OrderAddresses %>
	
	<% include Order %>
	  
	<% if Payments %>
	  <% include OrderPayments %>
	<% end_if %>
	
	<% if Downloads %>
	  <% include OrderDownloads %>
	<% end_if %>
	
	<% if CustomerUpdates %>
	  <% include OrderNotes %>
	<% end_if %>

</div>