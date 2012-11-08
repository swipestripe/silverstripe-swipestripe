	<table class="table table-bordered">
	  <thead>
	    <tr>
	      <th><% _t('Order.PRODUCT','Product') %></th>
        <th><% _t('Order.OPTIONS','Options') %></th>
        <th><% _t('Order.UNIT_PRICE','Unit Price') %> ($TotalPrice.Currency)</th>
        <th><% _t('Order.QUANTITY','Quantity') %></th>
        <th><% _t('Order.SUB_TOTAL','Sub Total') %> ($TotalPrice.Currency)</th>
	    </tr>
	  </thead>
	  <tbody>
	    <% control Items %>
	    
	      <tr  class="itemRow $EvenOdd $FirstLast">
	      
			    <% control Product %>  
			      <td>
			        <% if Link %>
			          <a href="$Link" target="_blank">$Title</a>
			        <% else %>
			          $Title
			        <% end_if %>
			      </td>
			    <% end_control %>
			    
			    <td>
				    $SummaryOfOptions
				  </td>
				  
				  <td>
            $UnitPrice.Nice
          </td>
		    
		      <td>
		        $Quantity
		      </td>
  
		      <td>$TotalPrice.Nice</td>
		    
		    </tr>
	    <% end_control %>
	    
	    <% if SubTotalModifications %>
        <% control SubTotalModifications %>
          <tr>
            <td colspan="4" class="row-header">$Description</td>
            <td>$Price.Nice</td>
          </tr>
        <% end_control %>
      <% end_if %>
	    
	    <tr>
        <td colspan="4" class="row-header"><% _t('Order.SUB_TOTAL','Sub Total') %></td>
        <td>$SubTotalPrice.Nice</td>
      </tr>
	    
	    <% if TotalModifications %>
	      <% control TotalModifications %>
	        <tr>
		        <td colspan="4" class="row-header">$Description</td>
		        <td>$Price.Nice</td>
		      </tr>
	      <% end_control %>
	    <% end_if %>
	
	    <tr>
	      <td colspan="4" class="row-header"><% _t('Order.TOTAL','Total') %></td>
	      <td>$TotalPrice.Nice</td>
	    </tr>
	  </tbody>
	</table>
