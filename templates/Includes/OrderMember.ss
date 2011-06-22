<table id="ShippingTable" class="infotable">
  <tr>
    <th>To</th>
  </tr>
  <tr>
    <td>
      <% control Member %>
        $Name<br />
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
