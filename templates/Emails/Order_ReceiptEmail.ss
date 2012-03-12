<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" >
		$InlineCSS
	</head>
	<body>
	
	  <h3>Hi $Customer.Name,</h3>
    $Message
	
	  <% control Order %>
      <div class="order">
        <table class="table table-bordered">
          <tr>
            <th>
              Order #$ID - $Status<br />
              <a href="$Link" id="OrderLink">View this order</a>
            </th>
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
       
        <% if Notes %>
          <% include OrderNotes %>
        <% end_if %>
      </div>
    <% end_control %>
    
    <p>
      Please note that orders will not be shipped until payment has been successfully processed.
    </p>
    
    $Signature
	</body>
</html>
