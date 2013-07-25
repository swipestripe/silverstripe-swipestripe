<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" >
		$InlineCSS
	</head>
	<body>

		<h3>Hi,</h3>
		$Message
	
		<% with Order %>
			<div class="order sws">
				<table class="table table-bordered">
					<tr>
						<th>
							<% _t('Order_NotificationEmail.ORDER','Order') %> #$ID - $Status<br />
							<a href="$Top.AdminLink" id="OrderLink">
								<% _t('Order_NotificationEmail.LOGIN','Log in to the CMS to manage this order') %>
							</a> 
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
	</body>
</html>