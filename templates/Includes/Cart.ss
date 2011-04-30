<h2>Cart is here</h2>

<% control Cart %>
  <ul>
	  <% control Items %>
	  
	    <li>
	    <% control Object %>$Title - $Amount.Nice<% end_control %> - $Quantity
	    </li>
	    
	  <% end_control %>
  </ul>

  $Total.Nice
  
<% end_control %>