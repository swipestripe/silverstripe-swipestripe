<div class="account-page sws">
	<div class="order">
	
	<% if Order %>
		<% with Order %>
		
			<table class="table table-bordered">
				<tr>
					<th>Order #$ID - $Status</th>
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
				<% include OrderPayments %>
			<% end_if %>
			
			<% if TotalOutstanding.Amount != 0 %>
				<p class="alert alert-error">
					<strong class="alert-heading"><% _t('AccountPage_order.WARNING','Warning!') %></strong>
					There is an outstanding amount on this order, please <a href="{$AbsoluteBaseURL}/account/repay/{$ID}">complete payment for this order here</a>.
				</p>
			<% end_if %>
			
			<% if CustomerUpdates %>
				<% include OrderNotes %>
			<% end_if %>
			
		<% end_with %>
	<% else %>
		<p class="alert alert-error">
			<strong class="alert-heading"><% _t('AccountPage_order.WARNING','Warning!') %></strong>
			$Message.Raw
		</p>
	<% end_if %>
	
	</div>

</div>
