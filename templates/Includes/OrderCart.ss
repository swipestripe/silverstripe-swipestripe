	<table id="InformationTable" class="infotable">
	  <thead>
	    <tr>
	      <th scope="col" class="left">Product</th>
	      <th scope="col" class="left">Quantity</th>
	      <th scope="col" class="right">Total Price ($Total.Currency)</th>
	    </tr>
	  </thead>
	  <tbody>
	    <% control Items %>
	    
		    <% control Object %>  
		    <tr  class="itemRow $EvenOdd $FirstLast">
		      <td class="product title" scope="row">
		        <% if Link %>
		          <a href="$Link" target="_blank">$Title</a>
		        <% else %>
		          $Title
		        <% end_if %>
		      </td>
		    <% end_control %>
		    
		      <td class="product title" scope="row">
		        $Quantity
		        
		        <% control Object %>
		          <a href="$RemoveFromCartLink">[-]</a>  
		          <a href="$AddToCartLink">[+]</a>
		        <% end_control %>
		      </td>
		      
		    <% control Object %>   
		      <td class="right total">$Amount.Nice</td>
		    </tr>
		    <% end_control %>
	    <% end_control %>
	
	    <tr class="gap summary total" id="Total">
	      <td scope="row" class="threeColHeader total">Total</td>
	      <td class="right" colspan="2">$Total.Nice ($Total.Currency)</td>
	    </tr>
	  </tbody>
	</table>
