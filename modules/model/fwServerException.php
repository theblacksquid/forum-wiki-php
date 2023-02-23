<?php

final class fwServerException extends Exception
{
    private static $Exceptions = [
        '000000000000' => 'Unknown Error.',
        '000000000001' => 'Assertion Failed.',
        '000000000002' => 'Config key does not exist.',

        // fwAuthorization
        '000100000000' => 'Username is already taken',
        '000100000001' => 'Username is too long',
    ];

    protected $code;
    protected $detail;

    public function __construct($errorCode, $detail = NULL)
    {
        $this->code = $errorCode;
        $this->detail = $detail;
    }

    public function getErrorCode()
    {
        return $this->code;
    }

    public static function getServerUTCTimestamp()
    {
        $date = new DateTime('now', new DateTimeZone('UTC'));

        return $date->getTimestamp();
    }

    public static function handleUnknownErrors(Exception $error)
    {
        print($error->getMessage() . "\r\n" . $error->getTraceAsString());
        echo self::outputJsonError(
            '000000000000',
            $error->getMessage());
    }
    
    public static function outputJsonError($errorCode, $errorDetail = NULL)
    {
        $errorMessage = '';
        $code = $errorCode;
        
        if ( isset(self::$Exceptions[$errorCode]) == FALSE )
        {
            $code = '000000000000';
            $errorMessage = self::$Exceptions[$code];
        }

        else
        {
            $errorMessage = self::$Exceptions[$errorCode];
        }

        $response =
        [
            'errorCode' => $code,
            'errorMessage' => $errorMessage,
            'timestamp' => self::getServerUTCTimestamp()
        ];

        if ( $errorDetail !== NULL )
        {
            $response['details'] = $errorDetail;
        }
        
        return json_encode($response);
    }
}

?>
