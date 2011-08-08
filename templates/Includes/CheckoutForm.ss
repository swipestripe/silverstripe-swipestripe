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
      
      <% control Fields %>
	      $FieldHolder
	    <% end_control %>
  
      <tr class="gap summary total" id="Total">
        <% control Cart %> 
        <td scope="row" class="threeColHeader total" colspan="4">Total</td>
        <td class="right">$Total.Nice</td>
        <% end_control %>
      </tr>
    </tbody>
  </table>


	<% if Actions %>
	<div class="Actions">
		<% control Actions %>
			$Field
		<% end_control %>
	</div>
	<% end_if %>
<% if IncludeFormTag %>
</form>
<% end_if %>
