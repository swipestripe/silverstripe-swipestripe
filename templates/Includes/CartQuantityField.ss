<tr>
  <td>
    $RemoveItemAction
  </td>

  <td> 
    <% if Item.Object.isPublished %>
      <a href="$Item.Object.Link" target="_blank">$Item.Object.Title</a>
    <% else %>
      $Item.Object.Title
    <% end_if %>
    
    <% if Message %>
      <div class="message $MessageType">
        $Message
      </div>
    <% end_if %>
  </td>
  
  <td>
    <% if Item.Variation %>
      $Item.Variation.SummaryOfOptions
    <% end_if %>
  </td>
  
  <td>
	  <% control Item %>   
	    $UnitPrice.Nice
	  <% end_control %>
  </td>

  <td>
    <div id="$Name" class="field $Type $extraClass">$titleBlock<div class="middleColumn">$Field</div>$rightTitleBlock</div>
  </td>
  
  <td>
	  <% control Item %>   
	    $Total.Nice
	  <% end_control %>
  </td>
</tr>
