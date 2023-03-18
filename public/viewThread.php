<?php

require_once(__DIR__ . '/../modules/view/fwView.php');
require_once(__DIR__ . "/../modules/model/fwUtils.php");
require_once(__DIR__ . "/../modules/model/fwConfigs.php");

$postListPath = __DIR__ . "/../modules/view/fwForum/postList.php";

$data = (function ()
{
    $_REQUEST['threadId'] = $_REQUEST['thread'];
    $_REQUEST['hash'] = fwUtils::generateHash($_REQUEST, fwConfigs::get('AuthSecret'));

    ob_start();
    require_once(__DIR__ . "/../modules/controller/fwForum/getThread.php");
    return ob_get_clean();
})();

$data = json_decode($data, TRUE);

$postList = fwView::component($postListPath, $data);

echo fwView::page('hello world', $postList);

?>
