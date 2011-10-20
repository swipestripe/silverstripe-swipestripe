
<tr  class="itemRow $EvenOdd $FirstLast">

  <td class="title" scope="row"> 
    <% if Item.Object.Published %>
      <a href="$Link" target="_blank">$Item.Object.Title</a>
    <% else %>
      $Item.Object.Title
    <% end_if %>
  </td>
  
  <td>
    <% if Item.Variation %>
      $Item.Variation.OptionSummary
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
