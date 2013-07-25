<div class="sws">

	<table class="table table-bordered">
		<tr>
			<th><% _t('OrderAdmin.ORDER','Order') %> #$ID - $Status</th>
		</tr>
		<tr>
			<td>
				$OrderedOn.Format(j M Y - g:i a)<br />
				($PaymentStatus)
			</td>
		</tr>
	</table>

	<% include Order %>
		
	<% if Payments %>
		<table class="table table-bordered">
			<thead>     
				<tr>
					<th><% _t('OrderPayments.PAYMENT','Payment') %></th>
					<th><% _t('OrderPayments.DATE','Date') %></th>
					<th><% _t('OrderPayments.AMOUNT','Amount') %></th>
					<th><% _t('OrderPayments.PAYMENT_STATUS','Payment Status') %></th>
				</tr>
			</thead>
			<tbody>
				<% loop Payments %>  
					<tr>
						<td>$Method</td>
						<td>$LastEdited.Nice24</td>
						<td>$Amount.Nice $Currency</td>
						<td>
							$Status

							<% if Errors %>
								<br />
								<% loop Errors %>
									<strong>$ErrorCode:</strong> $ErrorMessage<br />
								<% end_loop %>
							<% end_if %>

						</td>
					</tr>
				<% end_loop %>
			</tbody>
		</table>

		<table class="table table-bordered">
			<tbody>
				<tr>
					<th><% _t('OrderPayments.TOTAL_OUTSTANDING','Total outstanding') %></th>
					<th>$TotalOutstanding.Nice</th>
				</tr>
			</tbody>
		</table>
	<% end_if %>
	
	<% if CustomerUpdates %>
		<% include OrderNotes %>
	<% end_if %>

</div>