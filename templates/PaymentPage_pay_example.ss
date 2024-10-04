<!doctype html>
<html lang="$ContentLocale">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="format-detection" content="telephone=no">

	<title><% if MetaTitle %>$MetaTitle.XML<% else %>$Title.XML<% end_if %> | $SiteConfig.Title.XML</title>
	$MetaTags(false)

	<% base_tag %>

</head>
<body class="$ClassName" <% if $i18nScriptDirection %>dir="$i18nScriptDirection"<% end_if %> style="height: 100%;">

	<!-- Content Layout -->
	$Layout
	<!-- / Content Layout -->

</body>
</html>
