<div class="typography">
	
	<% if Order %>
    <% control Order %>
    
      <h2>Order #$ID - $Status <span class="payment_status">($PaymentStatus)</span></h2>
      <h3>$Created.Format(j M Y - g:i a)</h3>
      
      <div id="OrderInformation">

			  <% include OrderMember %>
      
        <% include Order %>
          
			  <% if Payments %>
			    <% include OrderPayments %>
			  <% end_if %>
			  
			  <% if Downloads %>
			    <% include OrderDownloads %>
			  <% end_if %>
			  
			</div>
      
    <% end_control %>
  <% else %>
    <div id="AccountMessage">$Message.Raw</div>
  <% end_if %>

</div>