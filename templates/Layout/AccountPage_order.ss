<div class="account-page sws">
  <div class="order">
  
	<% if Order %>
    <% control Order %>
    
      <table class="table table-bordered">
			  <tr>
			    <th>Order #$ID - $Status</th>
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
      
    <% end_control %>
  <% else %>
    <p class="alert alert-error">
		  <strong class="alert-heading"><% _t('AccountPage_order.WARNING','Warning!') %></strong>
		  $Message.Raw
		</p>
  <% end_if %>
  
  </div>
</div>