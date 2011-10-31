<% if IncludeFormTag %>
<form $FormAttributes>
<% end_if %>

  <% if Message %>
    <p id="{$FormName}_error" class="message $MessageType">$Message</p>
  <% else %>
    <p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
  <% end_if %>
  
  <fieldset>
  
    <div id="Addresses">
	    <div id="LeftCheckout">
		    <% control Fields(BillingAddress) %>
		      $FieldHolder
		    <% end_control %>
	    </div>
	    
	    <div id="RightCheckout">
	      <% control Fields(ShippingAddress) %>
	        $FieldHolder
	      <% end_control %>
	    </div>
    </div>
    
    <span class="seperator"></span>
    
    <% control Fields(PersonalDetails) %>
      $FieldHolder
    <% end_control %>
    
    <span class="seperator"></span>

    <div id="Order">
	    <h3>Your Order</h3>
	    <% include CheckoutFormOrder %>
    </div>
    
    <% control Fields(Notes) %>
      $FieldHolder
    <% end_control %>
    
    <span class="seperator"></span>
    
    <% control Fields(Payment) %>
      $FieldHolder
    <% end_control %>

    <div class="clear"><!-- --></div>
  </fieldset>

  <% if Cart.Items %>
	  <% if Actions %>
	  <div class="Actions">
	    <% control Actions %>
	      $Field
	    <% end_control %>
	  </div>
	  <% end_if %>
  <% end_if %>
  
<% if IncludeFormTag %>
</form>
<% end_if %>