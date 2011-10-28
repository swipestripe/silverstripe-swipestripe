
<tr  class="itemRow $EvenOdd $FirstLast">

  <td class="title" scope="row"> 
    <% if Item.Object.isPublished %>
      <a href="$Item.Object.Link" target="_blank">$Item.Object.Title</a>
    <% else %>
      $Item.Object.Title
    <% end_if %>
  </td>
  
  <td>
    <% if Item.Variation %>
      $Item.Variation.OptionSummary
    <% end_if %>
  </td>
  
  <td class="total">
	  $Item.UnitPrice.Nice
  </td>

  <td class="title" scope="row">
    $Item.Quantity
  </td>
  
  <td class="right total"> 
	  $Item.Total.Nice
  </td>

</tr>
