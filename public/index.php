<?php

require_once(__DIR__ . '/../modules/view/fwView.php');
require_once(__DIR__ . "/../modules/model/fwUtils.php");
require_once(__DIR__ . "/../modules/model/fwConfigs.php");
$boardListPath = __DIR__ . "/../modules/view/fwForum/boardList.php";
$loginPath = __DIR__ . '/../modules/view/fwAuthorization/loginView.php';

function getBoardList()
{
    $_REQUEST['hash'] = fwUtils::generateHash($_REQUEST, fwConfigs::get('AuthSecret'));
    ob_start();
    require_once(__DIR__ . "/../modules/controller/fwForum/getBoards.php");
    return ob_get_clean();
}

$loginForm = fwView::component($loginPath, [], NULL);

$boardData = json_decode(getBoardList(), TRUE);

$mainContent = fwView::component($boardListPath, $boardData);

echo fwView::page('hello world', implode('\n', [$loginForm, $mainContent]));

?>
