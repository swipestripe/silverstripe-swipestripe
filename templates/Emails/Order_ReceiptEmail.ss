<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" >
		$InlineCSS
	</head>
	<body>
	
	  <div class="Order typography">
	
			<h3>Hi $Customer.Name,</h3>
			$Message
	
	    <p><br /></p>
	
		  <% control Order %>
	    
	      <h3>Order #$ID - $Status <a href="$Link" id="OrderLink">View this order</a></h3>
	      
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
	      
	      <p>
        Please note that orders will not be shipped until payment has been successfully processed.
        </p>
	      
	    <% end_control %>
	    
	    $Signature
    
    </div>

	</body>
</html>
