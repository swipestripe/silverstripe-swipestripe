<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" >
		
		$InlineCSS
	</head>
	<body>
	
	<h3>Hi <% control Customer %>$FirstName,<% end_control %></h3>
	
	$Message

	<% control Order %>
		<% include Order %>
	<% end_control %>

	</body>
</html>
