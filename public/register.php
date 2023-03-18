<?php

require_once(__DIR__ . '/../modules/view/fwView.php');
$templatePath = __DIR__ . '/../modules/view/fwAuthorization/registerView.php';

$form = fwView::component($templatePath, [], NULL);

echo $form;

?>
