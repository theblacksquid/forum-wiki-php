<?php

require_once(__DIR__ . '/../modules/view/fwView.php');
require_once(__DIR__ . "/../modules/model/fwUtils.php");
require_once(__DIR__ . "/../modules/model/fwConfigs.php");

$threadListTemplate = __DIR__ . "/../modules/view/fwForum/threadList.php";

function callEndpoint()
{
    $_REQUEST['hash'] = fwUtils::generateHash($_REQUEST, fwConfigs::get('AuthSecret'));
    ob_start();
    require_once(__DIR__ . "/../modules/controller/fwForum/viewBoard.php");
    return ob_get_clean();
}

$threadList = fwView::component($threadListTemplate, json_decode(callEndpoint(), TRUE));

echo fwView::page('hello world', $threadList);

?>
