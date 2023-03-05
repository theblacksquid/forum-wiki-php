<?php

require_once(__DIR__ . '/configs.php');
    
class LoginController
{
    private static $requiredParameters = ['fwUserId', 'passwordHash', 'hash'];

    public static function checkSuspendAndLoginAttempts($request)
    {
        $dbConnection = new fwPDO(
            fwConfigs::get('DBUser'),
            fwConfigs::get('DBPassword'),
            'fwAuthorization'
        );

        $loginAttemptsQuery = "SELECT fwSecurity.failAttempts, 
                                  fwSecurity.lastUpdated,
                                  fwUsers.metadata 
                               FROM fwSecurity, fwUsers
                               WHERE fwSecurity.fwUserId = ? 
                               AND fwUsers.fwUserId = ? ";
    
        $loginAttempts = $dbConnection->query($loginAttemptsQuery,
                                              [$request['fwUserId'],
                                               $request['fwUserId']]);
    
        $lastFailedAttempt = fwUtils::getServerSideTimestamp() -
                           ((int) $loginAttempts[0]['lastUpdated']);
    
        // fwUtils::debugLog($loginAttempts);
        $accountMetadata = json_decode($loginAttempts[0]['metadata'], TRUE);

        if ( $accountMetadata['isSuspended'] == 1 )
        {
            // user is suspended
            throw new fwServerException('000100000004');
        }
    
        if ( ($lastFailedAttempt < (60 * 15)) &&
             ($loginAttempts[0]['failAttempts'] >= MAX_FAIL_ATTEMPTS) )
        {
            // too many failed logins
            throw new fwServerException('000100000003');
        }
    }

    public static function validateLogin($request)
    {
        $dbConnection = new fwPDO(
            fwConfigs::get('DBUser'),
            fwConfigs::get('DBPassword'),
            'fwAuthorization'
        );

        $passwordQuery = "SELECT passwordHash FROM fwUsers WHERE fwUserId = ?";
        $password = $dbConnection->query($passwordQuery, [$request['fwUserId']]);
        $password = $password[0]['passwordHash'];

        if ( $request['passwordHash'] !== $password )
        {
            $incrementFailedAttempts = "UPDATE fwSecurity 
                                        SET failAttempts = ( failAttempts + 1 ),
                                            lastUpdated = UNIX_TIMESTAMP(NOW())
                                        WHERE fwUserId = ?";

            $dbConnection->execute($incrementFailedAttempts, [$request['fwUserId']]);
        
            // password incorrect   
            throw new fwServerException('000100000002');
        }
    }

    public static function generateAuthToken($request)
    {
        $dbConnection = new fwPDO(
            fwConfigs::get('DBUser'),
            fwConfigs::get('DBPassword'),
            'fwAuthorization'
        );
        
        $expiry = fwUtils::getServerSideTimestamp();
        $expiry += (60 * 15);

        $token = md5(md5(json_encode($request) . microtime()));

        $updateTokenQuery = "UPDATE fwSecurity SET authToken = ?, failAttempts = 0 
                             WHERE fwUserId = ?";
    
        $dbConnection->execute($updateTokenQuery, [$token, $request['fwUserId']]);
    
        $token = $request['fwUserId'] . '|' . $token . '|' . $expiry;
    
        return fwUtils::outputJsonResponse(['authToken' => $token]);
    }

    public static function main($request)
    {
        try
        {
            // fwUtils::debugLog($request);
            fwUtils::verifyRequiredParameters(self::$requiredParameters, $request);
            fwUtils::verifyHash($request['hash'], $request, fwConfigs::get('AuthSecret'));
            self::checkSuspendAndLoginAttempts($request);
            self::validateLogin($request);

            echo self::generateAuthToken($request);
        }
        
        catch (fwServerException $error)
        {
            echo fwServerException::outputJsonError($error->getCode());
        }

        catch (Exception $error)
        {
            echo fwServerException::handleUnknownErrors($error);
        }
    }
}

?>
