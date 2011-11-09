
<div id="Product">

  <% control Product.FirstImage %>
    <div id="FirstImage">
      $Image.CroppedImage(250,250)
    </div>
  <% end_control %>

  <h1>$Product.Title</h1>
  
  <p id="ProductPrice">$Product.Amount.Nice <span id="VariationPrice"></span></p>

  <div id="ProductAdd">
    $AddToCartForm(1)
  </div>


  <div id="ProductDescription">
    $Product.Content
  </div>

</div>
