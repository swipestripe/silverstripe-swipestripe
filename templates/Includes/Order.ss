	<table class="table table-bordered">
	  <thead>
	    <tr>
	      <th><% _t('Order.PRODUCT','Product') %></th>
        <th><% _t('Order.OPTIONS','Options') %></th>
        <th><% _t('Order.UNIT_PRICE','Unit Price') %> ($Total.Currency)</th>
        <th><% _t('Order.QUANTITY','Quantity') %></th>
        <th><% _t('Order.SUB_TOTAL','Sub Total') %> ($Total.Currency)</th>
	    </tr>
	  </thead>
	  <tbody>
	    <% control Items %>
	    
	      <tr  class="itemRow $EvenOdd $FirstLast">
	      
			    <% control Object %>  
			      <td>
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
				    <% else %>
				      -
				    <% end_if %>
				  </td>
				  
				  <td>
            $UnitPrice.Nice
          </td>
		    
		      <td>
		        $Quantity
		      </td>
  
		      <td>$Total.Nice</td>
		    
		    </tr>
	    <% end_control %>
	    
	    <% if SubTotalModifications %>
        <% control SubTotalModifications %>
          <tr>
            <td colspan="4" class="row-header">$Description</td>
            <td>$Amount.Nice</td>
          </tr>
        <% end_control %>
      <% end_if %>
	    
	    <tr>
        <td colspan="4" class="row-header"><% _t('Order.SUB_TOTAL','Sub Total') %></td>
        <td>$SubTotal.Nice</td>
      </tr>
	    
	    <% if TotalModifications %>
	      <% control TotalModifications %>
	        <tr>
		        <td colspan="4" class="row-header">$Description</td>
		        <td>$Amount.Nice</td>
		      </tr>
	      <% end_control %>
	    <% end_if %>
	
	    <tr>
	      <td colspan="4" class="row-header"><% _t('Order.TOTAL','Total') %></td>
	      <td>$Total.Nice</td>
	    </tr>
	  </tbody>
	</table>
