<h3>Your Order</h3>

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
    <% control Items %>
    
      <tr  class="itemRow $EvenOdd $FirstLast">
      
		    <% control Object %>  
		      <td class="title">
		        <% if Link %>
		          <a href="$Link" target="_blank">$Title</a>
		        <% else %>
		          $Title
		        <% end_if %>
		      </td>
		    <% end_control %>
		    
		    <td>
			    <% if Variation %>
			      $Variation.OptionSummary
			    <% end_if %>
			  </td>
			  
			  <td>
           $UnitPrice.Nice
         </td>
	    
	      <td class="title">
	        $Quantity
	      </td>
 
	      <td class="right total">$Object.Amount.Nice</td>
	    
	    </tr>
    <% end_control %>
    
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
