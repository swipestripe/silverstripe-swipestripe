
<tr  class="itemRow $EvenOdd $FirstLast">

  <% control Item.Object %>  
    <td class="title" scope="row">
      <% if Link %>
        <a href="$Link" target="_blank">$Title</a>
      <% else %>
        $Title
      <% end_if %>
    </td>
  <% end_control %>


  <td>
  <% control Item.ItemOptions %>
    <% control Object %>
      $Title - $Amount.Nice
    <% end_control %>
  <% end_control %>
  </td>

  <td class="title" scope="row">
    <div id="$Name" class="field $Type $extraClass">$titleBlock<div class="middleColumn">$Field</div>$rightTitleBlock</div>
  </td>
  
  <% control Item.Object %>   
    <td class="right total">$Amount.Nice</td>
  <% end_control %>

</tr>
