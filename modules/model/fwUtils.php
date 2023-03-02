<?php

require_once('fwPDO.php');

class fwUtils
{
    public static function debugLog($value)
    {
        error_log(var_export($value, TRUE));
    }
    
    public static function generateHash($request, $key)
    {
        $json = json_encode($request);
        
        return md5(md5($json . $key));
    }

    public static function getServerSideTimestamp()
    {
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));

        return $dateTime->getTimestamp();
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

    public static function verifyRequiredParameters(array $paramNames, $request)
    {
        foreach ($paramNames as $param)
        {
            isset($request[$param]) || die('Missing required parameter ' . $param);
        }
    }

    public static function outputJsonResponse(array $response)
    {
        $currentTime = new DateTime('now', new DateTimeZone('UTC'));

        return json_encode(['status' => 'ok',
                            'result' => $response,
                            'timestamp' => $currentTime->getTimestamp()]);
    }

    public static function verifyAuthToken($authToken)
    {
        $split = explode('|', $authToken);

        if ( ((int) $split[2]) < self::getServerSideTimestamp() )
        {
            return FALSE;
        }
        
        $dbConnection = new fwPDO(
            fwConfigs::get('DBUser'),
            fwConfigs::get('DBPassword'),
            'fwAuthorization'
        );

        $tokenQuery = "SELECT * FROM fwSecurity 
                       WHERE fwUserId = ?
                       AND authToken = ?";
        
        $doesTokenExist = $dbConnection->query($tokenQuery, [$split[0], $split[1]]);

        if ( count($doesTokenExist) == 0 )
        {
            return FALSE;
        }

        return TRUE;
    }
}

?>
