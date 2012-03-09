<table class="table table-bordered">
  <thead>     
    <tr>
      <th>Payment</th>
      <th>Date</th>
      <th>Amount</th>
      <th>Payment Status</th>
    </tr>
  </thead>
  <tbody>
    <% control Payments %>  
      <tr>
        <td>$PaymentMethod</td>
        <td>$LastEdited.Nice24</td>
        <td>$Amount.Nice $Currency</td>
        <td>$Status</td>
      </tr>
    <% end_control %>
  </tbody>
</table>

<table class="table table-bordered">
  <tbody>
    <tr>
      <th>Total outstanding</th>
      <th>$TotalOutstanding.Nice</th>
    </tr>
  </tbody>
</table>