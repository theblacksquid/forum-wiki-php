<?php

require_once(__DIR__ . '/../modules/view/fwView.php');
require_once(__DIR__ . "/../modules/model/fwUtils.php");
require_once(__DIR__ . "/../modules/model/fwConfigs.php");

$postTemplate = __DIR__ . "/../modules/view/fwForum/viewPost.php";

$data = (function ()
{
    $_REQUEST['hash'] = fwUtils::generateHash($_REQUEST, fwConfigs::get('AuthSecret'));
    
    ob_start();
    require_once(__DIR__ . "/../modules/controller/fwForum/getPost.php");
    return ob_get_clean();
})();

$data = json_decode($data, TRUE);

isset($_REQUEST['thread']) ? $data['thread'] = $_REQUEST['thread'] : $data;

$post = fwView::component($postTemplate, $data);

echo fwView::page('hello world', $post);

?>
