<% if IncludeFormTag %>
<form $FormAttributes>
<% end_if %>

  <% if Message %>
    <p id="{$FormName}_error" class="message $MessageType">$Message</p>
  <% else %>
    <p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
  <% end_if %>
	
	<table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>&nbsp;</th>
        <th>Product</th>
        <th>Options</th>
        <th>Unit Price ($Cart.Total.Currency)</th>
        <th>Quantity</th>
        <th>Sub Total ($Cart.Total.Currency)</th>
      </tr>
    </thead>
    <tbody>
      
	    <% if Cart.Items %>
	    
	      <% control Fields %>
	        $FieldHolder
	      <% end_control %>
	      
	      <% control Cart %>
	      <tr>
		      <td colspan="5">&nbsp;</td>
		      <td><strong>$SubTotal.Nice ($SubTotal.Currency)</strong></td>
		    </tr>
		    <% end_control %>
	    
	    <% else %>
	      <tr>
      
	        <td colspan="6">
	          <p class="alert alert-info">
						  <strong class="alert-heading">Note:</strong>
						  There are no items in your cart.
						</p>
	        </td>
	
	      </tr>
	    <% end_if %>

    </tbody>
  </table>

  <% if Cart.Items %>
		<% if Actions %>
		<div class="Actions">
		
		  <p class="attribution">
        powered by <a href="http://swipestripe.com">SwipeStripe Ecommerce</a>
      </p>
		
			<% control Actions %>
				$Field
			<% end_control %>
		</div>
		<% end_if %>
	<% end_if %>
	
<% if IncludeFormTag %>
</form>
<% end_if %>