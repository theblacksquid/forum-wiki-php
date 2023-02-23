<?php

require_once(__DIR__ . '/fwServerException.php');

class fwConfigs
{
    public static function get($key)
    {
        $configs = include(__DIR__ . '/../../configs.php');

        if ( isset($configs[$key]) == FALSE )
        {
            throw new fwServerException('000000000002');
        }

        return $configs[$key];
    }
}

?>
