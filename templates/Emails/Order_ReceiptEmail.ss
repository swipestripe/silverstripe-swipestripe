<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" >
		
		$InlineCSS
	</head>
	<body>
	
	<h3>Hi <% control Customer %>$FirstName,<% end_control %></h3>
	
	$Message

	  <% control Order %>
    
      <h2>Order #$ID - $Status <span class="payment_status">($PaymentStatus)</span></h2>
      
      <div id="OrderInformation">

        <% include OrderMember %>
      
        <% include Order %>
          
        <% if Payments %>
          <% include OrderPayments %>
        <% end_if %>
        
      </div>
      
    <% end_control %>

	</body>
</html>
