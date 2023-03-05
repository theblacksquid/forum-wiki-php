<?php

require_once(__DIR__ . '/../modules/view/fwView.php');
$loginPath = __DIR__ . '/../modules/view/fwAuthorization/loginView.php';


$loginForm = fwView::component($loginPath, [], NULL);

$mainContent = ;

$body = implode("\n", [$loginForm, $mainContent]);

echo fwView::page('hello world', $body);

?>
