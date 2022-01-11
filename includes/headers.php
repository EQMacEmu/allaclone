<!DOCTYPE html>
<html>

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1" />
	<title><?php echo $SiteTitle . ' ' . ($Title != "" ? " :: $Title" : "") ?>
	</title>
	<link href="//fonts.googleapis.com/css?family=Raleway:400,700" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo $includes_url . $CssStyle; ?>.css" type="text/css" />
</head>

<body>
	<div id="site-wrapper">
		<header class="header">
			<div class="intro container">
				<div class="quick-search">
					<form name="fullsearch" method="GET" action="fullsearch.php">
						<input name="isearchtype" value="" type="hidden">
						<input onfocus="if(this.value == 'Quick Item Search...') { this.value = ''; }" onkeypress="var key=event.keyCode || event.which; if(key==13){ this.form.isearchtype.value = 'name'; this.form.submit(); } else {return true;}" name="iname" placeholder="Quick Item Search..." type="text">
					</form>
				</div>
				<div class="logo">
					<a href="<?php echo $root_url; ?>" title="Home"><?php echo $PageTitle; ?></a>
				</div>
			</div>
			<?php include("navbar.php"); ?>
		</header>
		<div id="main">
			<?php include("menu.php"); ?>
			<h2 class="page_title"><?php echo $Title; ?></h2>