<?php

require_once(__DIR__ . '/configs.php');

fwUtils::verifyRequiredParameters(['requestedUsername', 'password', 'hash']);

fwUtils::verifyHash($_REQUEST['hash'], $_REQUEST, fwConfigs::get('AuthSecret'));;

try
{   
    if ( strlen($_REQUEST['requestedUsername']) > 32 )
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
        [$_REQUEST['requestedUsername']]
    );

    if ( count($isUsernameTaken) !== 0 )
    {
        // username already taken
        throw new fwServerException('000100000000');
    }

    $insertNewUserQuery = "INSERT INTO fwUsers (username, passwordHash, dateRegistered) VALUES (?, ?, UNIX_TIMESTAMP(NOW()))";

    $dbConnection->execute($insertNewUserQuery, [$_REQUEST['requestedUsername'], $_REQUEST['password']]);

    $newUser = $dbConnection->query("SELECT * FROM fwUsers WHERE username = ?",
                                    [$_REQUEST['requestedUsername']]);

    echo json_encode($dbConnection->query("SELECT UNIX_TIMESTAMP(NOW())"));
    echo json_encode(['fwUserId' => '1234']);
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
