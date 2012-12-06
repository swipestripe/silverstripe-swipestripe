<tr>
  <td>
    <a href="#" data-item="$Item.ID" class="remove-item-js">remove</a>
  </td>

  <td> 
    <% if Item.Product.isPublished %>
      <a href="$Item.Product.Link" target="_blank">$Item.Product.Title</a>
    <% else %>
      $Item.Product.Title
    <% end_if %>
    
    <% if Message %>
      <div class="message $MessageType">
        $Message
      </div>
    <% end_if %>
  </td>
  
  <td>
    $Item.SummaryOfOptions
  </td>
  
  <td>
  	$Item.UnitPrice.Nice
  </td>

  <td>
    <div id="$Name" class="field $Type $extraClass">
    	$titleBlock
    	<div class="middleColumn">$Field</div>
    	$rightTitleBlock
    </div>
  </td>
  
  <td>
	  $Item.TotalPrice.Nice
  </td>
</tr>
