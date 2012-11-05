<!DOCTYPE html>
<html>
	<head>
	<% base_tag %>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=720, maximum-scale=1.0" />
	<title>$Title Settings</title>
</head>
<body class="loading cms sws" lang="$Locale.RFC1766" data-frameworkpath="$ModulePath(framework)">
	<% include CMSLoadingScreen %>
	
	<div class="cms-container center" data-layout-type="border">
		$Menu
		$SettingsContent
	</div>
	
	$EditorToolbar
</body>
</html>
