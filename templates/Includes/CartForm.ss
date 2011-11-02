<% if IncludeFormTag %>
<form $FormAttributes>
<% end_if %>
	<% if Message %>
	<p id="{$FormName}_error" class="message $MessageType">$Message</p>
	<% else %>
	<p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
	<% end_if %>
	
	<table id="InformationTable" class="infotable">
    <thead>
      <tr>
        <th scope="col" class="left">Product</th>
        <th scope="col" class="left">Options</th>
        <th scope="col" class="left">Unit Price ($Cart.Total.Currency)</th>
        <th scope="col" class="left">Quantity</th>
        <th scope="col" class="right">Sub Total ($Cart.Total.Currency)</th>
      </tr>
    </thead>
    <tbody>
      
	    <% if Cart.Items %>
	    
	      <% control Fields %>
	        $FieldHolder
	      <% end_control %>
	      
	      <% control Cart %>
	      <tr class="gap summary total" id="SubTotal">
		      <td class="threeColHeader total" colspan="4">Sub Total</td>
		      <td class="right">$SubTotal.Nice ($SubTotal.Currency)</td>
		    </tr>
		    <% end_control %>
	    
	    <% else %>
	      <tr  class="itemRow">
      
	        <td colspan="5">
	          <span class="error">There are no items in your cart.</span>
	        </td>
	
	      </tr>
	    <% end_if %>

    </tbody>
  </table>

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