<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
<link rel="stylesheet" type="text/css" href="style.css" />
<title>Myth Cutter</title>
<script type="text/javascript" src="jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="jquery.tmpl.min.js"></script>
<script type="text/javascript" src="scripts.js"></script>
</head>

<body>
	<div class="header"></div>

	<div class="content">


	<?php $viewbag->RenderContent() ?>
	</div>

	<div class="footer">
		<div class="left">
			MythCut <a href="?action=showChangelog" title="Show changelog"><?php echo $viewbag->Version ?>
			</a>
		</div>
		<div class="right">
			(c) 2011,2012 Mario Weilguni <a href="?action=showLicense">Licence</a>
		</div>
		<div class="clear" />
	</div>

</body>

</html>
