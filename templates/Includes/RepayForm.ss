<% if IncludeFormTag %>
<form $FormAttributes>
<% end_if %>

  <% if Message %>
    <p id="{$FormName}_error" class="message $MessageType">$Message</p>
  <% else %>
    <p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
  <% end_if %>

  <fieldset>
   
    <section class="payment-details">
	    <% control PaymentFields %>
	      $FieldHolder
	    <% end_control %>
    </section>

    <div class="clear" />
  </fieldset>

  <% if Cart.Items %>
	  <% if Actions %>
	  <div class="Actions">
	    <div class="loading">
	      <img src="swipestripe/images/loading.gif" />
	    </div>
	    <% control Actions %>
	      $Field
	    <% end_control %>
	  </div>
	  <% end_if %>
  <% end_if %>
  
<% if IncludeFormTag %>
</form>
<% end_if %>