<?php

class fwUtils
{
    public static function generateHash($request, $key)
    {
        $json = json_encode($request);
        
        return md5(md5($json . $key));
    }

    public static function verifyHash($hash, $request, $key)
    {
        unset($request['hash']);
        
        $expectedResult = self::generateHash($request, $key);
        if ( $hash !== $expectedResult )
        {
            // We halt all computation immediately to prevent spammers
            // from DDoS'ing us.
            die('Invalid Hash');
        }
    }

    public static function verifyRequiredParameters(array $paramNames)
    {
        foreach ($paramNames as $param)
        {
            isset($_REQUEST[$param]) || die('Missing required parameter ' . $param);
        }
    }

    public static function outputJsonResponse(array $response)
    {
        $currentTime = new DateTime('now', new DateTimeZone('UTC'));

        return json_encode(['status' => 'ok',
                            'result' => $response,
                            'timestamp' => $currentTime->getTimestamp()]);
    }
}

?>
