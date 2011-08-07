
	<table id="InformationTable" class="infotable">
	  <thead>
	    <tr>
	      <th scope="col" class="left">Product</th>
	      <th scope="col" class="left">Options</th>
	      <th scope="col" class="left">Quantity</th>
	      <th scope="col" class="right">Total Price ($Total.Currency)</th>
	    </tr>
	  </thead>
	  <tbody>
	    <% control Items %>
	    
	      <tr  class="itemRow $EvenOdd $FirstLast">
	      
			    <% control Object %>  
			      <td class="title" scope="row">
			        <% if Link %>
			          <a href="$Link" target="_blank">$Title</a>
			        <% else %>
			          $Title
			        <% end_if %>
			      </td>
			    <% end_control %>
			    
			    <td>
			    <% control ItemOptions %>
			      <% control Object %>
			        $Title - $Amount.Nice
			      <% end_control %>
			    <% end_control %>
			    </td>

		      <td class="title" scope="row">
		        $Quantity
		      </td>
		      
			    <% control Object %>   
			      <td class="right total">$Amount.Nice</td>
			    <% end_control %>
		    
		    </tr>
		    
	    <% end_control %>
	
	    <tr class="gap summary total" id="Total">
	      <td scope="row" class="threeColHeader total">Total</td>
	      <td class="right" colspan="2">$Total.Nice ($Total.Currency)</td>
	    </tr>
	  </tbody>
	</table>
