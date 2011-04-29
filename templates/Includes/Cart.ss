<h2>Cart is here</h2>

<% control Cart %>
  <ul>
	  <% control Items %>
	    <% control Object %>
	      <li>$Title - $Amount.Nice</li>
	    <% end_control %>
	  <% end_control %>
  </ul>

  $Total.Nice
  
<% end_control %>