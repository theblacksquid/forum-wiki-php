<?php function mainTemplate($title, $body)
{
    ob_start();
?>
<!DOCTYPE html>
<html>
    <head>
	<title><?php echo $title; ?></title>
	<meta name="viewport" content="width=device-width,initial-scale=1"/>
	<script src="./vendor/htmx.min.js"></script>
    </head>
    <body><?php echo $body; ?></body>
</html>
<?php 
    return ob_get_clean();
}

return 'mainTemplate';
?>
