
<div id="Product">

  <% control FirstImage %>
    <div id="FirstImage">
      $Image.CroppedImage(250,250)
    </div>
  <% end_control %>

  <h1>$Title</h1>
  
  <p id="ProductPrice">$Amount.Nice <span id="VariationPrice"></span></p>

  <div id="ProductAdd">
    $AddToCartForm(1)
  </div>
  
  <div id="ProductDescription">
    $Content
  </div>

</div>
