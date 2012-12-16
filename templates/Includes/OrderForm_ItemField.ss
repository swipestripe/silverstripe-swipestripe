<tr>
  <td> 
    <% if Item.Product.isPublished %>
      <a href="$Item.Product.Link" target="_blank">$Item.Product.Title</a>
    <% else %>
      $Item.Product.Title
    <% end_if %>
     
    <% if Message %>
	    <div class="message $MessageType">$Message</div>
	  <% end_if %>
  </td>
  
  <td>
    $Item.SummaryOfOptions
  </td>
  
  <td>
	  $Item.UnitPrice.Nice
  </td>

  <td>
    $Item.Quantity
  </td>
  
  <td> 
	  $Item.TotalPrice.Nice
  </td>
</tr>