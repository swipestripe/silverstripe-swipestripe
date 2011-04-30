<div class="typography">
	
	<% if Order %>
    <% control Order %>
      <h2>Order #$ID</h2>
      <h3>$Created.Nice</h3>
      
      <div id="OrderInformation">

			  <table id="ShippingTable" class="infotable">
			    <tr>
			      <th>To</th>
			    </tr>
			    <tr>
			      <td>
			        <% control Member %>
			          $FirstName $Surname<br />
			          $Address<br />
			          $AddressLine2<br />
			          $City<br />
			          $PostalCode<br />
			          $State<br />
			          $Country<br />
			        <% end_control %>
			      </td>
			    </tr>
			  </table>
      
        <% include Order %>
          
			  <% if Payments %>
			    <table id="PaymentTable" class="infotable">
			      <thead>     
			        <tr class="gap mainHeader">
			            <th colspan="10" class="left">Payment(s)</th>
			        </tr>
			        <tr>
			          <th scope="row" class="twoColHeader">Date</th>
			          <th scope="row"  class="twoColHeader">Amount</th>
			          <th scope="row"  class="twoColHeader">Payment Status</th>
			          <th scope="row" class="twoColHeader">Method</th>
			          <th scope="row" class="twoColHeader">Note</th>
			        </tr>
			        <% control Payments %>  
			          <tr>
			            <td class="price">$LastEdited.Nice24</td>
			            <td class="price">$Amount.Nice $Currency</td>
			            <td class="price">$Status</td>
			            <td class="price">$PaymentMethod</td>
			            <td class="price">$Message.NoHTML</td>
			          </tr>
			        <% end_control %>
			      </tbody>
			    </table>
			    
			    <table id="OutstandingTable" class="infotable">
			      <tbody>
			        <tr class="gap summary" id="Outstanding">
			          <th colspan="3" scope="row" class="threeColHeader"><strong>Total outstanding</strong></th>
			          <td class="right"><strong>$TotalOutstanding.Nice </strong></td>
			        </tr>
			      </tbody>
			    </table>
			  <% end_if %>
			  
			  
			</div>
      
    <% end_control %>
  <% else %>
    <div id="AccountMessage">$Message.Raw</div>
  <% end_if %>

</div>