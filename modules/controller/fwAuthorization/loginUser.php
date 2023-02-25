<?php

require_once(__DIR__ . '/configs.php');

fwUtils::verifyRequiredParameters(['fwUserId', 'passwordHash', 'hash']);

fwUtils::verifyHash($_REQUEST['hash'], $_REQUEST, fwConfigs::get('AuthSecret'));

try
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
                                          [$_REQUEST['fwUserId'],
                                           $_REQUEST['fwUserId']]);
    
    $lastFailedAttempt = fwUtils::getServerSideTimestamp() -
                         ((int) $loginAttempts[0]['lastUpdated']);
    
    fwUtils::debugLog($loginAttempts);
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
    
    $passwordQuery = "SELECT passwordHash FROM fwUsers WHERE fwUserId = ?";
    $password = $dbConnection->query($passwordQuery, [$_REQUEST['fwUserId']]);
    $password = $password[0]['passwordHash'];

    if ( $_REQUEST['passwordHash'] !== $password )
    {
        $incrementFailedAttempts = "UPDATE fwSecurity 
                                    SET failAttempts = ( failAttempts + 1 ),
                                        lastUpdated = UNIX_TIMESTAMP(NOW())
                                    WHERE fwUserId = ?";

        $dbConnection->execute($incrementFailedAttempts, [$_REQUEST['fwUserId']]);
        
        // password incorrect   
        throw new fwServerException('000100000002');
    }

    $expiry = fwUtils::getServerSideTimestamp();
    $expiry += (60 * 15);

    $token = md5(md5(json_encode($_REQUEST) . microtime()));

    $updateTokenQuery = "UPDATE fwSecurity SET authToken = ?, failAttempts = 0 
                         WHERE fwUserId = ?";
    
    $dbConnection->execute($updateTokenQuery, [$token, $_REQUEST['fwUserId']]);
    
    $token = $_REQUEST['fwUserId'] . '|' . $token . '|' . $expiry;
    
    echo fwUtils::outputJsonResponse(['authToken' => $token]);
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
