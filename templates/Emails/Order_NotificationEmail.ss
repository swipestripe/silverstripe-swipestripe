<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" >
		$InlineCSS
	</head>
	<body>
	
	  <div class="Order typography">
	
			<h3>Hi,</h3>
			$Message
			
			<p>&nbsp;</p>
	
		  <% control Order %>
	    
	      <h3>Order #$ID - $Status &nbsp;&nbsp;&nbsp; <a href="$Top.AdminLink" id="OrderLink">Log in to the CMS to manage this order</a></h3>
	      
	      <p class="OrderMeta">
	        $Created.Format(j M Y - g:i a)<br />
	        ($PaymentStatus)
	      </p>

	      <div id="OrderInformation">
	
	        <% include OrderAddresses %>
	      
	        <% include Order %>
	          
	        <% if Payments %>
	          <% include OrderPayments %>
	        <% end_if %>
	        
	        <% if Notes %>
	          <% include OrderNotes %>
	        <% end_if %>
	        
	      </div>
	      
	    <% end_control %>
    
    </div>

	</body>
</html>
