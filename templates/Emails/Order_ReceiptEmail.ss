<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" >
		<title><% _t("TITLE","Shop Receipt") %></title>
	</head>
	<body>
		<table id="Content" cellspacing="0" cellpadding="0" summary="Email Information">
			<thead>
				<tr>
					<th scope="col" colspan="2">
						<h1 class="title">$Subject</h1>
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td scope="row" colspan="2" class="typography">
						$Message
					</td>
				</tr>
				<% if Order %>
				<% control Order %>
					<tr>
						<td>
							
					    <table id="InformationTable" class="infotable">
					      <thead>
					        <tr>
					          <th scope="col" class="left">Product</th>
					          <th scope="col" class="right">Total Price ($Total.Currency)</th>
					        </tr>
					      </thead>
					      <tbody>
					        <% control Items %>
					        
					          <% control Object %>
					              
					          <tr  class="itemRow $EvenOdd $FirstLast">
					            <td class="product title" scope="row">
					              <% if Link %>
					                <a href="$Link">$Title</a>
					              <% else %>
					                $Title
					              <% end_if %>
					            </td>
					            <td class="right total">$Amount.Nice</td>
					          </tr>
					          <% end_control %>
					        <% end_control %>
					    
					        <tr class="gap summary total" id="Total">
					          <td scope="row" class="threeColHeader total">Total</td>
					          <td class="right">$Total.Nice ($Total.Currency)</td>
					        </tr>
					      </tbody>
					    </table>


						</td>
					</tr>
				<% end_control %>
				<% end_if %>
			</tbody>
		</table>
	</body>
</html>
