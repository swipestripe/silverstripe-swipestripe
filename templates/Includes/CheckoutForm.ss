<% if IncludeFormTag %>
<form $FormAttributes>
<% end_if %>

  <% if Message %>
    <p id="{$FormName}_error" class="message $MessageType">$Message</p>
  <% else %>
    <p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
  <% end_if %>
  
  <fieldset>
  
    <section class="addresses">
	    <div id="address-billing" class="address-left">
		    <% control Fields(BillingAddress) %>
		      $FieldHolder
		    <% end_control %>
	    </div>
	    
	    <div id="address-shipping" class="address-right">
	      <% control Fields(ShippingAddress) %>
	        $FieldHolder
	      <% end_control %>
	    </div>
    </section>
    
    <hr />
    
    <section class="personal-details">
	    <% control Fields(PersonalDetails) %>
	      $FieldHolder
	    <% end_control %>
    </section>
    
    <hr />

    <section class="order-details">
	    <h3>Your Order</h3>
	    <% include CheckoutFormOrder %>
    </section>
    
    <section class="notes">
	    <% control Fields(Notes) %>
	      $FieldHolder
	    <% end_control %>
    </section>
    
    <hr />
    
    <section class="payment-details">
	    <% control Fields(Payment) %>
	      $FieldHolder
	    <% end_control %>
    </section>

    <div class="clear" />
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