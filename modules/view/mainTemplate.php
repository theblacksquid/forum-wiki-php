<?php

require_once(__DIR__ . "/fwView.php");

?>


<?php function mainTemplate($title, $body)
{
    $loginTemplate = __DIR__ . "/fwAuthorization/loginView.php";
    $loginBar = fwView::component($loginTemplate, []);
    
    ob_start();
?>
<!DOCTYPE html>
<html>
    <head>
	<title><?php echo $title; ?></title>
	<meta name="viewport" content="width=device-width,initial-scale=1"/>
	<link rel="stylesheet" href="./vendor/w3.css" />
	<script src="./vendor/htmx.min.js"></script>
    </head>
    <body>
        <header>
            <nav>
                <a href="/"><?php echo fwConfigs::get('ForumName'); ?></a>
                <?php echo $loginBar; ?>
            </nav>
        </header>
    <?php echo $body; ?>
    </body>
</html>
<?php 
    return ob_get_clean();
}

return 'mainTemplate';
?>
