<?php

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

            $newBoard1 = $this->testAddBoard("Some Example Topic");
            $newBoard2 = $this->testAddBoard("Another Topic");
            array_push(self::$postHashes, $newBoard1['result']['boardId']);
            array_push(self::$postHashes, $newBoard2['result']['boardId']);
            

            $addModerator = $this->testAddModerator(
                $newBoard1['result']['boardId'],
                $user2['result']['fwUserId']
            );

            array_push(self::$postHashes, $addModerator['result']['moderatorId']);

            $moderators = $this->testGetModerators($newBoard1['result']['boardId']);

            $postText1 = "Etiam laoreet quam sed arcu.  Donec hendrerit tempor tellus.  Integer placerat tristique nisl.  Nam vestibulum accumsan nisl.  Fusce sagittis, libero non molestie mollis, magna orci ultrices dolor, at vulputate neque nulla lacinia eros.  ";
        
            $thread1 = $this->testNewThread(
                $user1['result']['fwUserId'],
                $authToken1['result']['authToken'],
                $postText1, "lorem ipsum",
                $newBoard1['result']['boardId']
            );

            $this->assertEquals(isset($thread1['result']['threadId']), TRUE);

            $postFail1 = $this->testNewThread(
                $user1['result']['fwUserId'],
                $authToken1['result']['authToken'],
                implode(array_fill(0, 1000, $postText1)),
                "lorem ipsum",
                $newBoard1['result']['boardId']
            );

            $this->assertEquals($postFail1['errorCode'], '000200000000');

            $postFail2 = $this->testNewThread(
                $user1['result']['fwUserId'],
                $authToken1['result']['authToken'],
                $postText1,
                implode(array_fill(0, 100, "lorem ipsum")),
                $newBoard1['result']['boardId']
            );

            $this->assertEquals($postFail2['errorCode'], '000200000001');

            array_push(self::$postHashes, $thread1['result']['threadId']);

            $newPost1 = $this->testNewPost(
                $user1['result']['fwUserId'],
                $authToken1['result']['authToken'],
                $postText1,
                $thread1['result']['threadId']
            );

            $this->assertEquals(isset($newPost1['result']['postId']), TRUE);
            array_push(self::$postHashes, $newPost1['result']['postId']);

            $threadData = $this->testGetThread($thread1['result']['threadId']);
            $this->assertEquals(count($threadData['result']), 2);
            
            $threadFail = $this->testGetThread('IDoNotExist');
            $this->assertEquals($threadFail['errorCode'], '000200000002');

            $this->testEditPost(
                $user1['result']['fwUserId'],
                $authToken1['result']['authToken'],
                "This is me replacing the contents of my previous post which used to have just some plain-ass lorem-ipsum placeholder stuff",
                $newPost1['result']['postId']
            );

            $afterEdit = $this->testGetThread($thread1['result']['threadId']);
            $this->assertEquals(
                ($threadData['result'][1]['postText'] !=
                 $afterEdit['result'][1]['postText']),
                TRUE
            );

            $this->testDeleteThread(
                $user1['result']['fwUserId'],
                $authToken1['result']['authToken'],
                $thread1['result']['threadId'],
                $newBoard1['result']['boardId']
            );

            $afterDelete = $this->testGetThread($thread1['result']['threadId']);
            $this->assertEquals($afterDelete['errorCode'], '000200000002');

            $toDelete = $this->testNewThread(
                $user2['result']['fwUserId'],
                $authToken2['result']['authToken'],
                $postText1,
                'This one is just gonna get deleted',
                $newBoard1['result']['boardId']
            );

            $this->testDeletePost(
                $user2['result']['fwUserId'],
                $authToken2['result']['authToken'],
                $toDelete['result']['threadId'],
                $newBoard1['result']['boardId']
            );

            $afterDelete2 = $this->testGetThread($toDelete['result']['threadId']);
            $this->assertEquals($afterDelete2['errorCode'], '000200000002');

            $this->testGetBoards();

            $someThread1 = $this->testNewThread(
                $user2['result']['fwUserId'],
                $authToken2['result']['authToken'],
                $postText1,
                'Some random thread about random shit',
                $newBoard1['result']['boardId']
            );

            array_push(self::$postHashes, $someThread1['result']['threadId']);

            $someThread2 = $this->testNewThread(
                $user2['result']['fwUserId'],
                $authToken2['result']['authToken'],
                $postText1,
                'another random thread',
                $newBoard1['result']['boardId']
            );

            array_push(self::$postHashes, $someThread2['result']['threadId']);

            $someThread3 = $this->testNewThread(
                $user2['result']['fwUserId'],
                $authToken2['result']['authToken'],
                $postText1,
                'random thread, the third',
                $newBoard1['result']['boardId']
            );

            array_push(self::$postHashes, $someThread3['result']['threadId']);

            $this->testViewBoard($newBoard1['result']['boardId']);

            $newPost2 = $this->testNewPost(
                $user1['result']['fwUserId'],
                $authToken1['result']['authToken'],
                $postText1,
                $someThread1['result']['threadId']
            );

            array_push(self::$postHashes, $newPost2['result']['postId']);

            $newPost3 = $this->testNewPost(
                $user2['result']['fwUserId'],
                $authToken2['result']['authToken'],
                $postText1 . "this content has to be unique",
                $someThread1['result']['threadId']
            );

            array_push(self::$postHashes, $newPost3['result']['postId']);

            $newPost4 = $this->testNewPost(
                $user1['result']['fwUserId'],
                $authToken1['result']['authToken'],
                $postText1 . "make it unique" ,
                $someThread1['result']['threadId']
            );

            array_push(self::$postHashes, $newPost4['result']['postId']);

            $this->testGetThread($someThread1['result']['threadId']);

            $this->testGetPost($newPost3['result']['postId']);

            $this->testCleanup(self::$userId, self::$postHashes);
        }

        catch (fwServerException $error)
        {
            echo "\n";
            echo fwServerException::outputJsonError($error->getCode(), $error->getDetails());
            $this->testCleanup(self::$userId, self::$postHashes);
        }
        
        catch (Exception $error)
        {
            echo fwServerException::handleUnknownErrors($error);
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
    
    public function testNewThread($fwUserId, $authToken, $postText, $title, $boardId)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";
        
        $params =
        [
            'fwUserId' => $fwUserId,
            'authToken' => $authToken,
            'postText' => $postText,
            'threadTitle' => $title,
            'threadVisibility' => "public",
            'board' => $boardId
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

    public function testEditPost($fwUserId, $authToken, $postText, $postId)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";
        
        $params =
        [
            'fwUserId' => $fwUserId,
            'authToken' => $authToken,
            'postText' => $postText,
            'postId' => $postId
        ];

        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));

        $response = self::testControllerUrl(
            'fwForum', 'editPost.php', $params, FALSE
        );

        return json_decode($response, TRUE);
    }

    public function testDeleteThread($fwUserId, $authToken, $threadId, $boardId)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";
        
        $params =
        [
            'fwUserId' => $fwUserId,
            'authToken' => $authToken,
            'threadId' => $threadId,
            'boardId' => $boardId
        ];

        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));

        $response = self::testControllerUrl(
            'fwForum', 'deleteThread.php', $params, FALSE
        );

        return json_decode($response, TRUE);
    }

    public function testDeletePost($fwUserId, $authToken, $postId, $boardId)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";
        
        $params =
        [
            'fwUserId' => $fwUserId,
            'authToken' => $authToken,
            'postId' => $postId,
            'boardId' => $boardId
        ];

        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));

        $response = self::testControllerUrl(
            'fwForum', 'deletePost.php', $params, FALSE
        );

        return json_decode($response, TRUE);
    }

    public function testAddBoard($boardName)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";
        
        $params =
        [
            'password' => fwConfigs::get('AuthSecret'),
            'boardName' => $boardName,
            'boardDescription' => 'This is some random placeholder text, ignore this'
        ];

        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));

        $response = self::testControllerUrl(
            'fwForum', 'addBoard.php', $params, FALSE
        );

        return json_decode($response, TRUE);
    }

    public function testAddModerator($boardId, $fwUserId)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";
        
        $params =
        [
            'password' => fwConfigs::get('AuthSecret'),
            'boardId' => $boardId,
            'fwUserId' => $fwUserId
        ];

        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));

        $response = self::testControllerUrl(
            'fwForum', 'addBoardModerator.php', $params, FALSE
        );

        return json_decode($response, TRUE);
    }

    public function testGetModerators($boardId)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";
        
        $params['boardId'] = $boardId;
        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));

        $response = self::testControllerUrl(
            'fwForum', 'getModerators.php', $params, FALSE
        );

        return json_decode($response, TRUE);
    }

    public function testGetBoards()
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";

        $params = [];
        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));

        $response = self::testControllerUrl(
            'fwForum', 'getBoards.php', $params, FALSE
        );

        return json_decode($response, TRUE);
    }

    public function testViewBoard($boardId)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";

        $params['board'] = $boardId;
        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));

        $response = self::testControllerUrl(
            'fwForum', 'viewBoard.php', $params, FALSE
        );

        return json_decode($response, TRUE);
    }

    public function testGetPost($postId)
    {
        echo "\r\n" . __FUNCTION__ . "\r\n";

        $params['post'] = $postId;
        $params['hash'] = fwUtils::generateHash($params, fwConfigs::get('AuthSecret'));

        $response = self::testControllerUrl(
            'fwForum', 'getPost.php', $params, FALSE
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
