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

            $this->testCleanup($this->userIdArray);
        }

        catch (Exception $error)
        {
            $this->testCleanup($this->userIdArray);
            echo $error->getMessage();
            echo $error->getTraceAsString();
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

    public function testCleanup(array $userIdArray)
    {
        $dbConnection = new fwPDO(
            fwConfigs::get('DBUser'),
            fwConfigs::get('DBPassword'),
            'fwAuthorization'
        );

        foreach ($userIdArray as $userId)
        {
            $dbConnection->execute("DELETE FROM fwUsers WHERE fwUserId = ?",
                                   [$userId]);
        }
    }
}

$tests = new tests();

$tests->main();

?>
