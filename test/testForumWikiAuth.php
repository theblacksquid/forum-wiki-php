<?php

require_once(__DIR__ . '/fwTestingFramework.php');
require_once(__DIR__ . '/../modules/model/fwUtils.php');
require_once(__DIR__ . '/../modules/model/fwConfigs.php');
require_once(__DIR__ . '/../modules/model/fwPDO.php');

class tests extends fwTestingFramework
{
    private $userIdArray = [];
    
    public function main()
    {
        try
        {
            $user1 = $this->testForumWikiRegister('testerTesterson', 'aSecurePassword');
            $user2 = $this->testForumWikiRegister('testerino_testmore', 'secureEnough');
            
            array_push($this->userIdArray, $user1['result']['fwUserId']);
            array_push($this->userIdArray, $user2['result']['fwUserId']);

            $this->assertEquals(isset($user1['result']['fwUserId']), TRUE);
            $this->assertEquals(isset($user2['result']['fwUserId']), TRUE);

            // Test for username already taken error
            $failRegister1 = $this->testForumWikiRegister(
                'testerTesterson',
                'aSecurePassword'
            );

            // test for 'username too long' error
            $failRegister2 = $this->testForumWikiRegister(
                implode(array_fill(0, 10, 'thisRegistrationShouldFail')),
                'aSecurePassword'
            );

            $this->assertEquals($failRegister1['errorCode'], '000100000000');
            $this->assertEquals($failRegister2['errorCode'], '000100000001');

            $token1 = $this->testForumWikiLogin($user1['result']['fwUserId'],
                                                'aSecurePassword');

            $this->assertEquals(
                fwUtils::verifyAuthToken($token1['result']['authToken']),
                TRUE
            );
            
            $token2 = $this->testForumWikiLogin($user2['result']['fwUserId'],
                                                'secureEnough');

            $this->assertEquals(
                fwUtils::verifyAuthToken($token2['result']['authToken']),
                TRUE
            );

            // test for password incorrect error
            $failLogin1 = $this->testForumWikiLogin($user1['result']['fwUserId'],
                                                   'incorrectPassword');
            
            $this->assertEquals($failLogin1['errorCode'], '000100000002');

            // spam incorrect logins to cause 'too many failed logins' error
            $this->tooManyLogins($user2['result']['fwUserId']);
            $failLogin2 = $this->testForumWikiLogin($user2['result']['fwUserId'],
                                                    'incorrectPassword');

            // test for 'too many failed logins' error
            $this->assertEquals($failLogin2['errorCode'], '000100000003');

            // rewrite timestamp to see if the logic for the 15-minute
            // timeout for too many logins is correct
            $this->rewriteTimestamp($user2['result']['fwUserId']);

            $login3 = $this->testForumWikiLogin($user2['result']['fwUserId'],
                                                    'incorrectPassword');


            $this->assertEquals($login3['errorCode'], '000100000002');

            // manually suspend test user to test for user suspension error
            $this->manualSuspend($user1['result']['fwUserId']);
            $failLogin3 = $this->testForumWikiLogin($user1['result']['fwUserId'],
                                                    'aSecurePassword');

            // test if user is suspended
            $this->assertEquals($failLogin3['errorCode'], '000100000004');
            
            $this->testCleanup($this->userIdArray);
        }

        catch (fwServerException $error)
        {
            $this->testCleanup($this->userIdArray);
            echo fwServerException::outputJsonError($error->getCode());
        }
        
        catch (Exception $error)
        {
            $this->testCleanup($this->userIdArray);
            echo fwServerException::handleUnknownErrors($error);
        }  
    }

    public function testForumWikiRegister($username, $password)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";
        
        $params =
        [
            'requestedUsername' => $username,
            'password' => md5($password)
        ];

        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));
        
        $response = self::testControllerUrl(
            'fwAuthorization',
            'registerUser.php',
            $params,
            FALSE
        );

        return json_decode($response, TRUE);
    }

    public function testForumWikiLogin($fwUserId, $password)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";
        
        $params =
        [
            'fwUserId' => $fwUserId,
            'passwordHash' => md5($password)
        ];

        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));
        
        $response = self::testControllerUrl(
            'fwAuthorization',
            'loginUser.php',
            $params,
            FALSE
        );

        return json_decode($response, TRUE);
    }

    public function rewriteTimestamp($userId)
    {
        echo "\r\n" . __FUNCTION__ ;
        $dbConnection = new fwPDO(
            fwConfigs::get('DBUser'),
            fwConfigs::get('DBPassword'),
            'fwAuthorization'
        );

        $query = "UPDATE fwSecurity 
                  SET lastUpdated = (lastUpdated - (60 * 16)) 
                  WHERE fwUserId = ?";

        $dbConnection->execute($query, [$userId]);
    }

    public function tooManyLogins($userId)
    {
        echo "\r\n" . __FUNCTION__ ;
        $dbConnection = new fwPDO(
            fwConfigs::get('DBUser'),
            fwConfigs::get('DBPassword'),
            'fwAuthorization'
        );

        $query = "UPDATE fwSecurity SET failAttempts = 5 WHERE fwUserId = ?";
        
        $dbConnection->execute($query, [$userId]);
    }
    
    public function manualSuspend($userId)
    {
        echo "\r\n" . __FUNCTION__ ;
        $dbConnection = new fwPDO(
            fwConfigs::get('DBUser'),
            fwConfigs::get('DBPassword'),
            'fwAuthorization'
        );

        $selectQuery = "SELECT metadata FROM fwUsers WHERE fwUserId = ?";

        $metadata = json_decode($dbConnection->query($selectQuery, [$userId]), TRUE);
        $metadata['isSuspended'] = 1;

        $writeQuery = "UPDATE fwUsers SET metadata = ? WHERE fwUserId = ?";

        $dbConnection->execute($writeQuery, [json_encode($metadata), $userId]);
    }
    
    public function testCleanup(array $userIdArray)
    {
        $dbConnection = new fwPDO(
            fwConfigs::get('DBUser'),
            fwConfigs::get('DBPassword'),
            'fwAuthorization'
        );
        
        foreach ($userIdArray as $userId)
        {
            $dbConnection->execute(
                "DELETE FROM fwUsers WHERE fwUserId = ?;
                 DELETE FROM fwSecurity WHERE fwUserId = ?;",
                [$userId, $userId]
            );
        }
    }
}

$tests = new tests();

$tests->main();

?>
