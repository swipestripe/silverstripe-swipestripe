<% if Products.MoreThanOnePage %>
	<div class="pagination">
	  <ul>
	  
	    <% if Products.PrevLink %>
	      <li>
	        <a href="$Products.PrevLink">Prev</a>
	      </li>
	    <% end_if %>
	    
	    <% control Products.Pages %>
	      <% if CurrentBool %>
	      <li class="active">
	        <a href="$Link">$PageNum</a>
	      </li>
	      <% else %>
	      <li>
	        <a href="$Link" title="Go to page $PageNum">$PageNum</a>
	      </li>
	      <% end_if %>
	    <% end_control %>
	    
	    <% if Products.NextLink %>
	      <li>
	        <a href="$Products.NextLink">Next</a>
	      </li>
	    <% end_if %>
	  </ul>
	</div>
<% end_if %>