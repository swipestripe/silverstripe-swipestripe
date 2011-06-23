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
	
		  <% control Order %>
	    
	      <h3><a href="$Link">Order #$ID - $Status</a></h3>
	      <p class="OrderMeta">
	        $Created.Format(j M Y - g:i a)<br />
	        ($PaymentStatus)
	      </p>
	      <p>
	      Please note that orders will not be shipped or available for download until 
	      payment has been successfully processed.
	      <% if Downloads %>
	      Downloads wil be available for this order from the <a href="$Link">order page in your members area</a>
	      once payment has been completed. You may download each product 3 times.
	      <% end_if %>
	      </p>
	      
	      <div id="OrderInformation">
	
	        <% include OrderMember %>
	      
	        <% include Order %>
	          
	        <% if Payments %>
	          <% include OrderPayments %>
	        <% end_if %>
	        
	      </div>
	      
	    <% end_control %>
    
    </div>

	</body>
</html>
