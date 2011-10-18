<% if Products %>

	<% include Pagination %>
	
	<div id="products_cont">
	  
	  <% control Products %>
	    <div class="product">
	      <div class="product_pic"><a href="$Link"><% control FirstImage %>$Image.CroppedImage(137,145)<% end_control %></a></div>
	      <div class="product_title"><a href="$Link">$Title.XML</a></div>
	      <div class="product_price">$Amount.Nice</div>
	    </div> <!-- .product -->
	
	    <% if MultipleOf(5) %>
	      <div class="clear"></div>
	    <% end_if %>
	  <% end_control %>
	</div> <!-- #products_cont -->
	
	<% include Pagination %>

<% else %>

	<h3>No Products</h3>
	<p>We're out of stock! We will add new products shortly, come back soon!</p>
	
<% end_if %>