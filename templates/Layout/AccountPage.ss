<h2>$Title</h2>
$Content

<% if Orders %>
	<ul id="Orders">
	<% control Orders %>
	  <li <% if Last %>class="LastOrder"<% end_if %>>
	    <div>
	      <h4><a href="$Link">Order #$ID - $Status <span class="payment_status">($PaymentStatus)</span></a></h4>
	      <p>
	        Date: $OrderedOn.Format(j M y). <br />
	        Total: $Total.Nice. <br />
	        
	        Items: 
	        <% control Products %>
	          <a href="$Link" target="_blank">$Title</a><% if Last %>.<% else %>, <% end_if %>
	        <% end_control %>
	        <br />
	        
	        <% if Downloads %>
	        <em>You can access downloads by <a href="$Link#DownloadsTable">viewing this order</a>.</em>
	        <% end_if %>
	      </p>
	    </div>
	  </li>
	<% end_control %>
	</ul>
<% end_if %>
