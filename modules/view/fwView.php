<?php

require_once(__DIR__ . '/../model/fwConfigs.php');
require_once(__DIR__ . '/../model/fwUtils.php');

class fwView
{   
    public static function page($title, $body)
    {
        $template = require_once(__DIR__ . '/mainTemplate.php');
        return $template($title, $body);
    }

    public static function component(
        $templatePath,
        array $data = [],
        callable $callback = NULL)
    {
        $template = require_once($templatePath);

        if ( $callback === NULL )
            $toRender = $data;
        else
            $toRender = $callback($data);
        
        return $template($toRender);
    }

    public static $VIEW_DIRECTORY = __DIR__;
}

echo fwView::$VIEW_DIRECTORY;

?>

