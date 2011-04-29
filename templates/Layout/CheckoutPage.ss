<div class="typography">
	<% if Menu(2) %>
		<% include SideBar %>
		<div id="Content">
	<% end_if %>

	<% if Level(2) %>
	  	<% include BreadCrumbs %>
	<% end_if %>
	
		<h2>$Title</h2>
	
		$Content

    <% control Cart %>
    <table id="InformationTable" class="infotable">
	    <thead>
	      <tr>
	        <th scope="col" class="left">Product</th>
	        <th scope="col" class="right">Total Price ($Total.Currency)</th>
	      </tr>
	    </thead>
	    <tbody>
	      <% control Items %>
	      
	        <% control Object %>
	            
	        <tr  class="itemRow $EvenOdd $FirstLast">
	          <td class="product title" scope="row">
	            <% if Link %>
	              <a href="$Link">$Title</a>
	            <% else %>
	              $Title
	            <% end_if %>
	          </td>
	          <td class="right total">$Amount.Nice</td>
	        </tr>
	        <% end_control %>
	      <% end_control %>
	  
	      <tr class="gap summary total" id="Total">
	        <td scope="row" class="threeColHeader total">Total</td>
	        <td class="right">$Total.Nice ($Total.Currency)</td>
	      </tr>
	    </tbody>
	  </table>
	  <% end_control %>

    $OrderForm

		$Form
		$PageComments
	<% if Menu(2) %>
		</div>
	<% end_if %>
</div>