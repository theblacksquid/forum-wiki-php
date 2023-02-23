<?php

require_once(__DIR__ . "/../modules/model/fwServerException.php");

class fwTestingFramework
{
    public static function assertEquals($valueX, $valueY)
    {
        if ( $valueX !==  $valueY )
        {
            throw new fwServerException('000000000001');
        }

        else
        {
            return TRUE;
        }
    }

    public static function testControllerUrl($folder, $endpoint, $requestParams, $shouldError)
    {
        unset($_REQUEST);
        $_REQUEST = $requestParams;
        ob_start();
        require(__DIR__ . '/../modules/controller/' . $folder . '/' . $endpoint);
        return ob_get_contents();
    }
}

?>
