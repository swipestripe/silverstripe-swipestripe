<% control Cart %>
<table id="checkout-order-table" class="table table-bordered">
  <thead>
    <tr>
      <th><% _t('CheckoutFormOrder.PRODUCT', 'Product') %></th>
      <th><% _t('CheckoutFormOrder.OPTIONS', 'Options') %></th>
      <th><% _t('CheckoutFormOrder.UNIT_PRICE', 'Unit Price') %> ($TotalPrice.Currency)</th>
      <th><% _t('CheckoutFormOrder.QUANTITY', 'Quantity') %></th>
      <th><% _t('CheckoutFormOrder.SUB_TOTAL', 'Sub Total') %> ($TotalPrice.Currency)</th>
    </tr>
  </thead>
  <tbody>
  
    <% if Items %>
    
      <% control Top.ItemsFields %>
	      $FieldHolder
	    <% end_control %>
	    
    <% else %>
      <tr>
        <td colspan="5">
          <div class="error"><% _t('CheckoutFormOrder.NO_ITEMS_IN_CART','There are no items in your cart.') %></div>
        </td>
      </tr>
    <% end_if %>

    <% control Top.SubTotalModificationsFields %>
      $FieldHolder
    <% end_control %>
    
    <tr>
      <td colspan="4" class="row-header"><% _t('CheckoutFormOrder.SUB_TOTAL','Sub Total') %></td>
      <td>$SubTotalPrice.Nice</td>
    </tr>
    
    <% control Top.TotalModificationsFields %>
      $FieldHolder
    <% end_control %>

    <tr>
      <td colspan="4" class="row-header"><% _t('CheckoutFormOrder.TOTAL','Total') %></td>
      <td>$TotalPrice.Nice</td>
    </tr>

  </tbody>
</table>
<% end_control %>