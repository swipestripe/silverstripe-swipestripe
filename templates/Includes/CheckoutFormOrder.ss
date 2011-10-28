<% control Cart %>
<table id="InformationTable" class="infotable">
  <thead>
    <tr>
      <th scope="col" class="left">Product</th>
      <th scope="col" class="left">Options</th>
      <th scope="col" class="left">Unit Price ($Total.Currency)</th>
      <th scope="col" class="left">Quantity</th>
      <th scope="col" class="right">Sub Total ($Total.Currency)</th>
    </tr>
  </thead>
  <tbody>
    <% if Items %>
    
      <% control Top.Fields(Items) %>
	      $FieldHolder
	    <% end_control %>
	    
    <% else %>
      <tr  class="itemRow">
      
        <td colspan="5">
          <span class="error">There are no items in your cart.</span>
        </td>

      </tr>
    <% end_if %>
    <tr class="gap summary total" id="SubTotal">
      <td class="threeColHeader total" colspan="4">Sub Total</td>
      <td class="right">$SubTotal.Nice ($SubTotal.Currency)</td>
    </tr>
    
    <% control Top.Fields(Modifiers) %>
      $FieldHolder
    <% end_control %>

    <tr class="gap summary total" id="Total">
      <td class="threeColHeader total" colspan="4">Total</td>
      <td class="right">$Total.Nice ($Total.Currency)</td>
    </tr>
  </tbody>
</table>
<% end_control %>