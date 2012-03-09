
<div class="product">

  <div class="product-image">
	  <% control Product.FirstImage %>
	    $Image.CroppedImage(250,250)
	  <% end_control %>
  </div>

  <div class="product-meta">
	  <h1>$Product.Title</h1>
	  <h3 class="product-price">$Product.Amount.Nice</h3>
	
	  <div class="add-to-cart">
	    $AddToCartForm(1)
	  </div>
  </div>

  <div class="product-description">
    $Product.Content
  </div>

</div>
