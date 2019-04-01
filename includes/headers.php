<!DOCTYPE html>
    <html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1" />
        <title><?php echo $SiteTitle.' '.( $Title != "" ? " :: $Title" : "") ?></title>
        <link href="//fonts.googleapis.com/css?family=Raleway:400,700" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo $includes_url . $CssStyle; ?>.css" type="text/css" />
    </head>
    <body>
        <header>
            <div class="intro container">
                <a href="<?php echo $root_url; ?>" title="Home"><?php echo $PageTitle; ?></a>
            </div>
            <?php include("navbar.php"); ?>
        </header>
        <div id="main">
        <?php include("menu.php"); ?>
        <h2 class="page_title"><?php echo $Title; ?></h2>