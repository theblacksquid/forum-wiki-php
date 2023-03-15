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
        '000100000002' => 'Password incorrect',
        '000100000003' => 'Too many failed login attempts.',
        '000100000004' => 'User is suspended',

        // fwForum
        '000200000000' => 'Post text is too long',
        '000200000001' => 'Post title is too long',
        '000200000002' => 'threadId not found',
        '000200000003' => 'postId not found',
        '000200000004' => 'User is not author of post/thread or moderator',
        '000200000005' => 'boardName is too long',
        '000200000006' => 'Admin Panel Error: Incorrect secret hash',
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
        $timestamp = (new DateTime('now', new DateTimeZone('UTC')))->getTimestamp();
        
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
            'timestamp' => $timestamp
        ];

        if ( $errorDetail !== NULL )
        {
            $response['details'] = $errorDetail;
        }
        
        return json_encode($response);
    }
}

?>
