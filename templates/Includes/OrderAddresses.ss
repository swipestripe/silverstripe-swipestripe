<table id="ShippingTable" class="infotable">
  <tr>
    <th>Billing Address</th>
    <th>Shipping Address</th>
  </tr>
  <tr>
    <td>
      <% control BillingAddress %>
        $FirstName $Surname 
      <% end_control %>  
        
        <% if MemberEmail %><a href="Mailto:$MemberEmail">$MemberEmail</a><% end_if %><br />
        
      <% control BillingAddress %>
        <% if Company %>$Company<br /><% end_if %>
        <% if Address %>$Address<br /><% end_if %>
        <% if AddressLine2 %>$AddressLine2<br /><% end_if %>
        <% if City %>$City<br /><% end_if %>
        <% if PostalCode %>$PostalCode<br /><% end_if %>
        <% if State %>$State<br /><% end_if %>
        <% if Country %>$Country<br /><% end_if %>
      <% end_control %>
    </td>
    
    <td>
      <% control ShippingAddress %>
        $FirstName $Surname <br />
        <% if Company %>$Company<br /><% end_if %>
        <% if Address %>$Address<br /><% end_if %>
        <% if AddressLine2 %>$AddressLine2<br /><% end_if %>
        <% if City %>$City<br /><% end_if %>
        <% if PostalCode %>$PostalCode<br /><% end_if %>
        <% if State %>$State<br /><% end_if %>
        <% if Country %>$Country<br /><% end_if %>
      <% end_control %>
    </td>
  </tr>
</table>
