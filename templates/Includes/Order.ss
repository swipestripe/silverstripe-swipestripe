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
				      $Variation.SummaryOfOptions
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
	    
	    <% if Modifications %>
	    
	      <tr class="gap summary total" id="SubTotal">
          <td class="threeColHeader total" colspan="4">Sub Total</td>
          <td class="right">$SubTotal.Nice ($SubTotal.Currency)</td>
        </tr>
	    
	      <% control Modifications %>
	        <tr class="gap total">
		        <td class="threeColHeader" colspan="4">$Description</td>
		        <td class="right">$Amount.Nice ($Amount.Currency)</td>
		      </tr>
	      <% end_control %>
	    <% end_if %>
	
	    <tr class="gap summary total" id="Total">
	      <td class="threeColHeader total" colspan="4">Total</td>
	      <td class="right">$Total.Nice ($Total.Currency)</td>
	    </tr>
	  </tbody>
	</table>
