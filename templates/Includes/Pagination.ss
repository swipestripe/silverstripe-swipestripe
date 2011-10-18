<% if Products.MoreThanOnePage %>

	<div class="pag_cont">
	  <div class="ld">
	  
	    <% if Products.PrevLink %>
		    <a href="$Products.PrevLink">&lt;</a>
		  <% end_if %>
		  
		  <% control Products.Pages %>
		    <% if CurrentBool %>
		      <a href="$Link" class="current">$PageNum</a>
		    <% else %>
		      <a href="$Link" title="Go to page $PageNum">$PageNum</a>
		    <% end_if %>
		  <% end_control %>
		  
		  <% if Products.NextLink %>
		    <a href="$Products.NextLink">&gt;</a>
		  <% end_if %>
		  
	  </div>
	  
	  <div class="clear"></div>
	</div> <!-- .pag_cont -->

<% end_if %>