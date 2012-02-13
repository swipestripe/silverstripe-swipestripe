
<tr  class="itemRow $EvenOdd $FirstLast">

  <td>
    $RemoveItemAction
  </td>

  <td class="title" scope="row"> 
    <% if Item.Object.isPublished %>
      <a href="$Item.Object.Link" target="_blank">$Item.Object.Title</a>
    <% else %>
      $Item.Object.Title
    <% end_if %>
    
    <% if Message %>
      <span class="message $MessageType">$Message</span>
    <% end_if %>
    
  </td>
  
  <td>
    <% if Item.Variation %>
      $Item.Variation.SummaryOfOptions
    <% end_if %>
  </td>
  
  <td class="right total">
	  <% control Item %>   
	    $UnitPrice.Nice
	  <% end_control %>
  </td>

  <td class="title" scope="row">
    <div id="$Name" class="field $Type $extraClass">$titleBlock<div class="middleColumn">$Field</div>$rightTitleBlock</div>
  </td>
  
  <td class="right total">
	  <% control Item %>   
	    $Total.Nice
	  <% end_control %>
  </td>

</tr>
