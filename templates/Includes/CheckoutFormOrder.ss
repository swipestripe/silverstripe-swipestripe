<% control Cart %>
<table id="checkout-order-table" class="table table-bordered">
  <thead>
    <tr>
      <th>Product</th>
      <th>Options</th>
      <th>Unit Price ($Total.Currency)</th>
      <th>Quantity</th>
      <th>Sub Total ($Total.Currency)</th>
    </tr>
  </thead>
  <tbody>
  
    <% if Items %>
    
      <% control Top.Fields(Items) %>
	      $FieldHolder
	    <% end_control %>
	    
    <% else %>
      <tr>
        <td colspan="5">
          <div class="error">There are no items in your cart.</div>
        </td>
      </tr>
    <% end_if %>
    
    <% control Top.Fields(SubTotalModifiers) %>
      $FieldHolder
    <% end_control %>
    
    <tr>
      <td colspan="4" class="row-header">Sub Total</td>
      <td>$SubTotal.Nice ($SubTotal.Currency)</td>
    </tr>
    
    <% control Top.Fields(Modifiers) %>
      $FieldHolder
    <% end_control %>

    <tr>
      <td colspan="4" class="row-header">Total</td>
      <td>$Total.Nice ($Total.Currency)</td>
    </tr>
  </tbody>
</table>
<% end_control %>