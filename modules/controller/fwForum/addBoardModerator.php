<?php

require_once(__DIR__ . '/fwForum.php');

try
{
    $dbController = new fwPDO(
        fwConfigs::get('DBUser'),
        fwConfigs::get('DBPassword'),
        'fwGraph'
    );

    echo fwForum::addBoardModerator($dbController, $_REQUEST);
}

catch (fwServerException $error)
{
    echo fwServerException::outputJsonError($error->getCode());
}

catch (Exception $error)
{
    echo fwServerException::handleUnknownErrors($error);
}


?>
