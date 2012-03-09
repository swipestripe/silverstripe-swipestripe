<tr>
  <td> 
    <% if Item.Object.isPublished %>
      <a href="$Item.Object.Link" target="_blank">$Item.Object.Title</a>
    <% else %>
      $Item.Object.Title
    <% end_if %>
     
    <% if Message %>
	    <div class="message $MessageType">$Message</div>
	  <% end_if %>
  </td>
  
  <td>
    <% if Item.Variation %>
      $Item.Variation.SummaryOfOptions
    <% end_if %>
  </td>
  
  <td>
	  $Item.UnitPrice.Nice
  </td>

  <td>
    $Item.Quantity
  </td>
  
  <td> 
	  $Item.Total.Nice
  </td>
</tr>