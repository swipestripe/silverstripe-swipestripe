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
        <th><% _t('CartForm.PRODUCT', 'Product') %></th>
        <th><% _t('CartForm.OPTIONS', 'Options') %></th>
        <th><% _t('CartForm.UNIT_PRICE', 'Unit Price') %> ($Cart.TotalPrice.Currency)</th>
        <th><% _t('CartForm.QUANTITY', 'Quantity') %></th>
        <th><% _t('CartForm.SUB_TOTAL', 'Sub Total') %> ($Cart.TotalPrice.Currency)</th>
      </tr>
    </thead>
    <tbody>
      
	    <% if Cart.Items %>
	    
	      <% loop Fields %>
	        $FieldHolder
	      <% end_loop %>
	      
	      <% with Cart %>
	      <tr>
		      <td colspan="5">&nbsp;</td>
		      <td><strong>$CartTotalPrice.Nice</strong></td>
		    </tr>
		    <% end_with %>
	    
	    <% else %>
	      <tr>
      
	        <td colspan="6">
	          <p class="alert alert-info">
						  <strong class="alert-heading"><% _t('CartForm.NOTE', 'Note:') %></strong>
						  <% _t('CartForm.NO_ITEMS_IN_CART', 'There are no items in your cart.') %>
						</p>
	        </td>
	
	      </tr>
	    <% end_if %>

    </tbody>
  </table>

  <div class="Actions">
		<p class="attribution">
		  <% _t('CartForm.POWERED_BY','powered by') %> <a target="_blank" href="http://swipestripe.com">SwipeStripe Ecommerce</a>
		</p>
     
	  <% if Cart.Items %>
			<% if Actions %>

				<% control Actions %>
					$Field
				<% end_control %>
			
			<% end_if %>
		<% end_if %>
	</div>
	
<% if IncludeFormTag %>
</form>
<% end_if %>