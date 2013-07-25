<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" >
		$InlineCSS
	</head>
	<body>
	
		<h3><% _t('Order_ReceiptEmail.GREETING', 'Hi') %> $Customer.Name,</h3>
		$Message
	
		<% with Order %>
			<div class="order sws">
				<table class="table table-bordered">
					<tr>
						<th>
							<% _t('Order_ReceiptEmail.ORDER', 'Order') %> #$ID - $Status<br />
							<a href="$Link" id="OrderLink"><% _t('Order_ReceiptEmail.VIEW_ORDER', 'View this order') %></a> 
						</th>
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
			 
				<% if CustomerUpdates %>
					<% include OrderNotes %>
				<% end_if %>
			</div>
		<% end_with %>
		
		<p>
			<% _t('Order_ReceiptEmail.PAYMENTNOTICE', 'Please note that orders will not be shipped until payment has been successfully processed.') %>
		</p>
		
		$Signature
	</body>
</html>
