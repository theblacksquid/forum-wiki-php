<?php

use tests as GlobalTests;

require_once(__DIR__ . '/fwTestingFramework.php');
require_once(__DIR__ . '/../modules/model/fwUtils.php');
require_once(__DIR__ . '/../modules/model/fwConfigs.php');
require_once(__DIR__ . '/../modules/model/fwPDO.php');

class tests extends fwTestingFramework
{
    private static array $postHashes = [];
    private static array $userId = [];

    public function main()
    {
        try
        {
            $user1 = $this->testForumWikiRegister('testingmore', 'aSecurePassword');
            $user2 = $this->testForumWikiRegister('testingson', 'anotherPassword');
            array_push(self::$userId, $user1['result']['fwUserId']);
            array_push(self::$userId, $user2['result']['fwUserId']);

            $authToken1 = $this->testForumWikiLogin(
                $user1['result']['fwUserId'],
                'aSecurePassword'
            );

            $authToken2 = $this->testForumWikiLogin(
                $user2['result']['fwUserId'],
                'anotherPassword'
            );

            $postText1 = "Etiam laoreet quam sed arcu.  Donec hendrerit tempor tellus.  Integer placerat tristique nisl.  Nam vestibulum accumsan nisl.  Fusce sagittis, libero non molestie mollis, magna orci ultrices dolor, at vulputate neque nulla lacinia eros.  ";
        
            $thread1 = $this->testNewThread(
                $user1['result']['fwUserId'],
                $authToken1['result']['authToken'],
                $postText1, "lorem ipsum"
            );

            $this->assertEquals(isset($thread1['result']['threadId']), TRUE);

            $postFail1 = $this->testNewThread(
                $user1['result']['fwUserId'],
                $authToken1['result']['authToken'],
                implode(array_fill(0, 1000, $postText1)),
                "lorem ipsum"
            );

            $this->assertEquals($postFail1['errorCode'], '000200000000');

            $postFail2 = $this->testNewThread(
                $user1['result']['fwUserId'],
                $authToken1['result']['authToken'],
                $postText1,
                implode(array_fill(0, 100, "lorem ipsum"))
            );

            $this->assertEquals($postFail2['errorCode'], '000200000001');

            array_push(self::$postHashes, $thread1['result']['threadId']);

            $newPost1 = $this->testNewPost(
                $user1['result']['fwUserId'],
                $authToken1['result']['authToken'],
                $postText1,
                $thread1['result']['threadId']
            );

            array_push(self::$postHashes, $newPost1['result']['newPost']);

            $threadData = $this->testGetThread($thread1['result']['threadId']);
            $this->assertEquals(count($threadData['result']), 2);

            $this->testCleanup(self::$userId, self::$postHashes);
        }

        catch (Exception $error)
        {
            $this->testCleanup(self::$userId, self::$postHashes);
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
    
    public function testNewThread($fwUserId, $authToken, $postText, $title)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";
        
        $params =
        [
            'fwUserId' => $fwUserId,
            'authToken' => $authToken,
            'postText' => $postText,
            'threadTitle' => $title,
            'threadVisibility' => "public",
            'board' => 'testing'
        ];

        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));

        $response = self::testControllerUrl(
            'fwForum', 'newThread.php', $params, FALSE
        );

        return json_decode($response, TRUE);
    }

    public function testNewPost($fwUserId, $authToken, $postText, $threadId)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";
        
        $params =
        [
            'fwUserId' => $fwUserId,
            'authToken' => $authToken,
            'postText' => $postText,
            'threadId' => $threadId
        ];

        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));

        $response = self::testControllerUrl(
            'fwForum', 'newPost.php', $params, FALSE
        );

        return json_decode($response, TRUE);
    }

    public function testGetThread($threadId)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";
        
        $params['threadId'] = $threadId;
        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));

        $response = self::testControllerUrl(
            'fwForum', 'getThread.php', $params, FALSE
        );

        return json_decode($response, TRUE);
    }

    public function testCleanup(array $userIds, array $threadId)
    {
        $dbConnection = new fwPDO(
            fwConfigs::get('DBUser'),
            fwConfigs::get('DBPassword'),
            'fwAuthorization'
        );

        $query = "DELETE FROM fwUsers WHERE fwUserId = ?";

        foreach ($userIds as $user)
        {
            $dbConnection->execute($query, [$user]);
        }

        $deleteNode = "DELETE FROM fwGraph.fwGraphNodes WHERE nodeKey = ?";
        $deleteEdge = "DELETE FROM fwGraph.fwGraphEdges WHERE edgeFrom IN " .
                      "(SELECT nodeId from fwGraph.fwGraphNodes WHERE nodeKey = ?) ";

        foreach ($threadId as $thread)
        {
            $dbConnection->execute($deleteEdge, [$thread]);            
            $dbConnection->execute($deleteNode, [$thread]);
        }
    }
}

$test = new tests();

$test->main();

?>
