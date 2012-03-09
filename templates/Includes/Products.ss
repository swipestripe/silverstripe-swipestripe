<% if Products %>

	<% include Pagination %>

  <div class="product-row">
	  <% control Products %>
	    <div class="product-brief">
	    
	      <div class="product-brief-image">
	        <a href="$Link">
	        <% control FirstImage %>
	          $Image.CroppedImage(137,145)
	        <% end_control %>
	        </a>
	      </div>
	      
	      <h5 class="product-brief-title">
	        <a href="$Link">$Title.XML</a>
	      </h5>
	      
	      <p class="product-brief-price">
	        $Amount.Nice
	      </p>
	      
	    </div>
	
	    <% if Last %>
	    </div>
	    <% else %>
		    <% if MultipleOf(4) %>
		      </div><div class="product-row">
		    <% end_if %>
	    <% end_if %>
	    
	  <% end_control %>
  
	
	<% include Pagination %>

<% else %>

	<h3>No Products</h3>
	<p>We're out of stock! We will add new products shortly, come back soon!</p>
	
<% end_if %>