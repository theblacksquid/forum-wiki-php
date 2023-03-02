<?php

require_once(__DIR__ . '/configs.php');

class RegisterController
{

    private static $requiredParameters = ['requestedUsername', 'password', 'hash'];

    public static function checkUsername($request)
    {
        if ( strlen($request['requestedUsername']) > 32 )
        {
            // username too long
            throw new fwServerException('000100000001');
        }
    
        $dbConnection = new fwPDO(
            fwConfigs::get('DBUser'),
            fwConfigs::get('DBPassword'),
            'fwAuthorization'
        );
    
        $isUsernameTaken = $dbConnection->query(
            "SELECT * FROM fwUsers WHERE userName = ?",
            [$request['requestedUsername']]
        );

        if ( count($isUsernameTaken) > 0 )
        {
            // username already taken
            throw new fwServerException('000100000000');
        }
    }

    public static function insertNewUser($request)
    {
        $dbConnection = new fwPDO(
            fwConfigs::get('DBUser'),
            fwConfigs::get('DBPassword'),
            'fwAuthorization'
        );
        
        $metadata = json_encode(['isSuspended' => 0]);
        $insertNewUserQuery = "INSERT INTO fwUsers (username, passwordHash, dateRegistered, metadata) 
                           VALUES (?, ?, UNIX_TIMESTAMP(NOW()), ?)";

        $dbConnection->execute($insertNewUserQuery,
                               [$request['requestedUsername'],
                                $request['password'],
                                $metadata]);

        $newUser = $dbConnection->query("SELECT * FROM fwUsers WHERE username = ?",
                                        [$request['requestedUsername']]);

        $insertSecurityRow = "INSERT INTO fwSecurity VALUES (?, '', 0, UNIX_TIMESTAMP(NOW()))";
        $dbConnection->execute($insertSecurityRow, [$newUser[0]['fwUserId']]);

        return $newUser;
    }

    public static function main($request)
    {
        try
        {
            fwUtils::verifyRequiredParameters(self::$requiredParameters, $request);
            fwUtils::verifyHash($request['hash'], $request, fwConfigs::get('AuthSecret'));
            self::checkUsername($request);
            $newUser = self::insertNewUser($request);

            echo fwUtils::outputJsonResponse(['fwUserId' => $newUser[0]['fwUserId']]);
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
