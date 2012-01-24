
<div id="Product">

  <% control Product.FirstImage %>
    <div id="FirstImage">
      $Image.CroppedImage(250,250)
    </div>
  <% end_control %>

  <h1>$Product.Title</h1>
  
  <p id="ProductPrice"><span id="PriceTotal">$Product.Amount.Nice</span> <span id="VariationPrice"></span></p>

  <div id="ProductAdd">
    <% if Product.InStock %>
      $AddToCartForm(1)
    <% else %>
      <p>Sorry this product is out of stock. Please check back soon.</p>
    <% end_if %>
  </div>


  <div id="ProductDescription">
    $Product.Content
  </div>

</div>
