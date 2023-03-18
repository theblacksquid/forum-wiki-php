<?php

require_once(__DIR__ . '/config.php');

class fwForum
{
    private static function doesPostIdExist(fwPDO $dbController, $postId)
    {
        $query = "SELECT COUNT(*) FROM fwGraphNodes " .
                 "WHERE nodeType = 'post' AND nodeKey = ?";

        $response = $dbController->query($query, [$postId]);

        return ( count($response) > 0 );
    }
    
    private static function insertPostEdges(fwPDO $dbController, $hash)
    {
        $edgeInsert =
                    "INSERT INTO fwGraphEdges (edgeType, edgeFrom, edgeTo, edgeData) " .
                    "SELECT fwGraphNodes.nodeType, ?, fwGraphNodes.nodeKey, '' " .
                    "FROM fwGraphNodes " .
                    "WHERE fwGraphNodes.nodeKey = ? " .
                    "AND NOT fwGraphNodes.nodeType = 'post';";

        $dbController->execute($edgeInsert, [$hash, $hash]);
    }

    private static function deleteEdges(fwPDO $dbController, $postId)
    {
        $deleteEdgesQuery = "DELETE FROM fwGraphEdges WHERE edgeType IN" .
                          "( " .
                          "    SELECT edgeType FROM fwGraphEdges WHERE edgeFrom = ? " .
                          ") " .
                          "AND edgeFrom IN " .
                          "( " .
                          "    SELECT edgeTo FROM fwGraphEdges " .
                          "    WHERE edgeType = 'post' AND edgeFrom = ? " .
                          ")";

        $dbController->execute($deleteEdgesQuery,
                               [$postId, $postId]);
    }

    private static function groupNodesByNodeKeys($postKeys, $postDataArray)
    {
        $dataProcessPipe = new Collection($postDataArray);
        $postKeysMap = new Collection($postKeys);

        return $postKeysMap->map(
            fn ($key) => $dataProcessPipe->filter(
                fn ($data) => $data['nodeKey'] == $key
            )->get()
        )->get();
    }

    private static function getDetailsFromNodes($nodes, $nodeType, array $fields)
    {
        $data = new Collection($nodes);

        $filter = fn ($node) => in_array($node['nodeType'], $fields, TRUE);

        $map = fn ($node) =>
             $node['nodeType'] == $nodeType ?
             [$nodeType, $node['nodeKey']] :
             [$node['nodeType'], $node['nodeMeta']];

        $reduce = function ($prev, $next) { $prev[$next[0]] = $next[1]; return $prev; };

        return $data->filter($filter)
                    ->map($map)
                    ->reduce($reduce, array())
                    ->get();
    }

    private static function isUserModerator(fwPDO $dbController, $boardId, $fwUserId)
    {
        $query = "SELECT * FROM fwGraphNodes " .
                 "WHERE nodeType = 'boardModerator' " .
                 "AND nodeKey IN " .
                 "( " .
                 "    SELECT edgeTo FROM fwGraphEdges " .
                 "    WHERE edgeType = 'boardModerator' " .
                 "    AND edgeFrom = ?" .
                 ") ";

        $response = $dbController->query($query, [$boardId]);
        $response = array_filter($response, fn ($x) => $x['nodeMeta'] == $fwUserId);

        return ( count($response) > 0 );
    }

    private static function isUserPostAuthor(fwPDO $dbController, $postId, $fwUserId)
    {
        $query = "SELECT nodeMeta FROM fwGraphNodes " .
                 "WHERE nodeType = 'postAuthor' " .
                 "AND nodeKey = ?";

        $response = $dbController->query($query, [$postId]);

        return ( $response[0]['nodeMeta'] === $fwUserId );
    }

    private static function doesBoardExist(fwPDO $dbController, $boardId)
    {
        $doesBoardExistQuery = "SELECT * FROM fwGraphNodes " .
                             "WHERE nodeType = 'board' " .
                             "AND nodeKey = ?";

        $doesBoardExist = $dbController->query($doesBoardExistQuery,
                                               [$boardId]);

        return ( count($doesBoardExist) > 0 );
    }
    
    public static function newThread(fwPDO $dbController, array $request)
    {
        try
        {
            fwUtils::verifyRequiredParameters(
                ['fwUserId', 'authToken', 'postText',
                 'threadTitle', 'threadVisibility', 'board', 'hash'],
                $request
            );

            fwUtils::verifyHash($request['hash'], $request, fwConfigs::get('AuthSecret'));
            
            if ( fwUtils::verifyAuthToken($request['authToken']) == FALSE )
            {
                // invalid authtoken
                throw new fwServerException('000200000005');                
            }

            if ( strlen($request['threadTitle']) > fwConfigs::get('MaxTitleLength') )
            {
                // title too long
                throw new fwServerException('000200000001');
            }

            if ( strlen($request['postText']) > fwConfigs::get('MaxPostLength') )
            {
                // post content too long
                throw new fwServerException('000200000000');
            }

            $nodeInsert =
                        "INSERT INTO fwGraphNodes (nodeType, nodeKey, nodeMeta) " .
                        "VALUES " .
                        "('post', :hash, '')," .
                        "('thread', :hash, '')," .
                        "('postDate', :hash, UNIX_TIMESTAMP(NOW()))," .
                        "('postText', :hash, :postText)," .
                        "('postAuthor', :hash, :postAuthor)," .
                        "('threadTitle', :hash, :threadTitle)," .
                        "('threadAuthor', :hash, :postAuthor)," .
                        "('threadVisibility', :hash, :threadVisibility); " .
                        "INSERT INTO fwGraphNodes (nodeType, nodeKey, nodeMeta) " .
                        "SELECT 'postAuthorUsername', :hash, username " .
                        "FROM fwAuthorization.fwUsers WHERE fwUserId = :postAuthor ";

            $nodeInsertParams =
            [
                'postAuthor' => $request['fwUserId'],
                'postText' => $request['postText'],
                'hash' => $request['hash'],
                'threadVisibility' => $request['threadVisibility'],
                'threadTitle' => $request['threadTitle']
            ];

            $dbController->execute($nodeInsert, $nodeInsertParams);

            self::insertPostEdges($dbController, $request['hash']);

            $insertInitialPost = "INSERT INTO fwGraphEdges " .
                                 "(edgeType, edgeFrom, edgeTo, edgeData) " .
                                 "VALUES " .
                                 "('post', ?, ?, 0), " .
                                 "('thread', ?, ?, '')";

            $dbController->execute(
                $insertInitialPost,
                [$request['hash'], $request['hash'],
                 $request['board'], $request['hash']]);

            echo fwUtils::outputJsonResponse(['threadId' => $request['hash']]);
        }

        catch (Exception $error)
        {
            throw $error;
        }
    }

    public static function getThread(fwPDO $dbController, array $request)
    {
        fwUtils::verifyRequiredParameters(['threadId', 'hash'], $request);
        fwUtils::verifyHash($request['hash'], $request, fwConfigs::get('AuthSecret'));

        $postKeyQuery = "SELECT edgeTo FROM fwGraphEdges " .
                        "WHERE edgeType = 'post' AND edgeFrom = ? " .
                        "ORDER BY edgeData";

        $postKeys = $dbController->query($postKeyQuery, [$request['threadId']]);
        $postKeys = array_column($postKeys, "edgeTo");

        if ( count($postKeys) == 0 )
        {
            // threadId not found
            throw new fwServerException('000200000002');
        }

        $listParamString = implode(",", array_fill(0, count($postKeys), "?"));
        $postDataQuery = "SELECT * from fwGraphNodes WHERE nodeKey IN ( $listParamString )";
        $postData = $dbController->query($postDataQuery, $postKeys);

        $groupedByPost = self::groupNodesByNodeKeys($postKeys, $postData);
        $groupedByPost = array_map(
            fn ($post) => self::getDetailsFromNodes(
                $post, 'post',
                ["postAuthor", "postDate", "postText", "post", "postAuthorUsername"]),
            $groupedByPost);

        return fwUtils::outputJsonResponse($groupedByPost);
    }
    
    public static function deleteThread(fwPDO $dbController, array $request)
    {
        try
        {
            fwUtils::verifyRequiredParameters(
                ['fwUserId', 'authToken', 'threadId', 'boardId', 'hash'],
                $request
            );

            fwUtils::verifyHash($request['hash'], $request, fwConfigs::get('AuthSecret'));

            if ( fwUtils::verifyAuthToken($request['authToken']) == FALSE )
            {
                // invalid authtoken
                throw new fwServerException('000200000005');                
            }

            if ( self::doesPostIdExist($dbController, $request['hash']) == FALSE )
            {
                // post not found
                throw new fwServerException('000200000002');
            }

            $isUserPostAuthor = self::isUserPostAuthor(
                $dbController, $request['threadId'], $request['fwUserId']);

            $isUserModerator = self::isUserModerator(
                $dbController, $request['boardId'], $request['fwUserId']);

            if ( ($isUserModerator || $isUserPostAuthor) == FALSE )
            {
                // User is not author/moderator
                throw new fwServerException('000200000004');
            }

            $deleteNodesQuery = "DELETE FROM fwGraphNodes WHERE nodeKey IN " .
                                "( " .
                                "    SELECT edgeTo FROM fwGraphEdges " .
                                "    WHERE edgeType = 'post' AND edgeFrom = ? " .
                                ")";

            $dbController->execute($deleteNodesQuery, [$request['threadId']]);

            self::deleteEdges($dbController, $request['threadId']);

            return fwUtils::outputJsonResponse([]);
            
        }

        catch (Exception $error)
        {
            throw $error;
        }
    }

    public static function newPost(fwPDO $dbController, array $request)
    {
        try
        {
            fwUtils::verifyRequiredParameters(
                ['fwUserId', 'authToken', 'threadId', 'postText', 'hash'],
                $request
            );

            fwUtils::verifyHash($request['hash'], $request, fwConfigs::get('AuthSecret'));

            if ( fwUtils::verifyAuthToken($request['authToken']) == FALSE )
            {
                // invalid authtoken
                throw new fwServerException('000200000005');                
            }

            if ( strlen($request['postText']) > fwConfigs::get('MaxPostLength') )
            {
                // post content too long
                throw new fwServerException('000200000000');
            }

            if ( self::doesPostIdExist($dbController, $request['threadId']) == FALSE )
            {
                // threadId not found
                throw new fwServerException('000200000002');
            }

            $nodeInsert = "INSERT INTO fwGraphNodes (nodeType, nodeKey, nodeMeta) ".
                          "VALUES " .
                          "('post', :hash, ''), " .
                          "('postAuthor', :hash, :postAuthor), " .
                          "('postDate', :hash, UNIX_TIMESTAMP(NOW())), " .
                          "('postText', :hash, :postText); " .
                          "INSERT INTO fwGraphNodes (nodeType, nodeKey, nodeMeta) " .
                          "SELECT 'postAuthorUsername', :hash, username " .
                          "FROM fwAuthorization.fwUsers WHERE fwUserId = :postAuthor ; ";

            $nodeInsertParams =
                [
                    'hash' => $request['hash'],
                    'threadId' => $request['threadId'],
                    'postAuthor' => $request['fwUserId'],
                    'postText' => $request['postText']
                ];

            $dbController->execute($nodeInsert, $nodeInsertParams);

            $countPostsInThread = "SELECT COUNT(*) FROM fwGraphEdges " .
                                  "WHERE edgeType = 'post' AND edgeFrom = ?";
            
            $countPosts = $dbController->query($countPostsInThread, [$request['threadId']]);
            $countPosts = $countPosts[0]['COUNT(*)'];

            $insertNextPostEdge = "INSERT INTO fwGraphEdges " .
                                  "(edgeType, edgeFrom, edgeTo, edgeData) " .
                                  "VALUES " .
                                  "('post', ?, ?, ?) ";
            
            $dbController->execute(
                $insertNextPostEdge,
                [$request['threadId'], $request['hash'], $countPosts]
            );

            self::insertPostEdges($dbController, $request['hash']);

            $outputQuery = "SELECT nodeKey AS posts " .
                           "FROM fwGraphNodes WHERE nodeId IN " .
                           "( " .
                           "    SELECT edgeTo FROM fwGraphEdges " .
                           "    WHERE edgeType = 'post' AND edgeFrom = ? " .
                           ") ";
            
            $output = $dbController->query($outputQuery, [$request['threadId']]);
            $output = array_column($output, "posts");

            return fwUtils::outputJsonResponse(
                ['postId' => $request['hash'], 'posts' => $output]);
        }

        catch (Exception $error)
        {
            throw $error;
        }  
    }

    public static function editPost(fwPDO $dbController, array $request)
    {
        try
        {
            fwUtils::verifyRequiredParameters(
                ['fwUserId', 'authToken', 'postId', 'postText', 'hash'],
                $request
            );

            fwUtils::verifyHash($request['hash'], $request, fwConfigs::get('AuthSecret'));

            if ( fwUtils::verifyAuthToken($request['authToken']) == FALSE )
            {
                // invalid authtoken
                throw new fwServerException('000200000005');                
            }
            
            if ( strlen($request['postText']) > fwConfigs::get('MaxPostLength') )
            {
                // post text too long
                throw new fwServerException('000200000000');
            }

            if ( self::doesPostIdExist($dbController, $request['postId']) == FALSE )
            {
                // postId not found
                throw new fwServerException('000200000003');
            }

            $query = "UPDATE fwGraphNodes SET nodeMeta = ? " .
                     "WHERE nodeType = 'postText' AND nodeKey = ? ";

            $dbController->execute($query, [$request['postText'], $request['postId']]);

            return fwUtils::outputJsonResponse([]);
        }

        catch (Exception $error)
        {
            throw $error;
        }
    }

    public static function deletePost(fwPDO $dbController, array $request)
    {
        try
        {
            fwUtils::verifyRequiredParameters(
                ['fwUserId', 'authToken', 'postId', 'boardId', 'hash'],
                $request
            );

            fwUtils::verifyHash($request['hash'], $request, fwConfigs::get('AuthSecret'));

            if ( fwUtils::verifyAuthToken($request['authToken']) == FALSE )
            {
                // invalid authtoken
                throw new fwServerException('000200000005');                
            }

            if ( self::doesPostIdExist($dbController, $request['hash']) == FALSE )
            {
                // post not found
                throw new fwServerException('000200000002');
            }

            $isUserPostAuthor = self::isUserPostAuthor(
                $dbController, $request['postId'], $request['fwUserId']);

            $isUserModerator = self::isUserModerator(
                $dbController, $request['boardId'], $request['fwUserId']);

            if ( ($isUserModerator || $isUserPostAuthor) == FALSE )
            {
                // User is not author/moderator
                throw new fwServerException('000200000004');
            }

            $deleteNodesQuery = "DELETE FROM fwGraphNodes WHERE nodeKey = ?";

            $dbController->execute($deleteNodesQuery, [$request['postId']]);

            self::deleteEdges($dbController, $request['postId']);

            return fwUtils::outputJsonResponse([]);
        }

        catch (Exception $error)
        {
            throw $error;
        }
    }

    public static function addBoard(fwPDO $dbController, array $request)
    {
        try
        {
            fwUtils::verifyRequiredParameters(
                ['password', 'boardName', 'boardDescription'],
                $request
            );

            if ( $request['password'] != fwConfigs::get('AuthSecret') )
            {
                // Admin Panel Error: Incorrect secret hash
                throw new fwServerException('000200000006');
            }

            if ( strlen($request['boardName']) > fwConfigs::get('MaxTitleLength') )
            {
                // Board name too long
                throw new fwServerException('000200000005');
            }

            $boardKey = fwUtils::generateHash($request['boardName'], fwConfigs::get('AuthSecret'));
            
            $insertBoard = "INSERT INTO fwGraphNodes " .
                           "(nodeType, nodeKey, nodeMeta) " .
                           "VALUES " .
                           "('board', ?, ?), " . 
                           "('boardDescription', ?, ?)," .
                           "('boardName', ?, ?)";

            $dbController->execute($insertBoard,
                                   [$boardKey, $request['boardName'],
                                    $boardKey, $request['boardDescription'],
                                    $boardKey, $request['boardName']]);

            return fwUtils::outputJsonResponse(['boardId' => $boardKey]);
        }

        catch (Exception $error)
        {
            throw $error;
        }
    }
    
    public static function addBoardModerator(fwPDO $dbController, array $request)
    {
        try
        {
            fwUtils::verifyRequiredParameters(
                ['password', 'boardId', 'fwUserId'],
                $request
            );

            if ( $request['password'] != fwConfigs::get('AuthSecret') )
            {
                // Admin Panel Error: Incorrect secret hash
                throw new fwServerException('000200000006');
            }

            $moderatorId = fwUtils::generateHash(
                $request['boardId'] . $request['fwUserId'],
                fwConfigs::get('AuthSecret')
            );

            $insertModeratorQuery = "INSERT INTO fwGraphNodes " .
                                    "(nodeType, nodeKey, nodeMeta) " .
                                    "VALUES ('boardModerator', ?, ?);" .
                                    "INSERT INTO fwGraphEdges " .
                                    "(edgeType, edgeFrom, edgeTo, edgeData) " .
                                    "VALUES ('boardModerator', ?, ?, '');";

            $dbController->execute($insertModeratorQuery,
                                   [$moderatorId, $request['fwUserId'],
                                    $request['boardId'], $moderatorId]);

            return fwUtils::outputJsonResponse(['moderatorId' => $moderatorId]);
        }

        catch (Exception $error)
        {
            throw $error;
        }
    }

    public static function getBoards(fwPDO $dbController, array $request)
    {
        try
        {
            fwUtils::verifyRequiredParameters(['hash'], $request);
            fwUtils::verifyHash($request['hash'], $request, fwConfigs::get('AuthSecret'));

            $getBoardAndDescription = "SELECT * FROM fwGraphNodes " .
                                      "WHERE nodeKey IN " .
                                      "( " .
                                      "    SELECT nodeKey FROM fwGraphNodes " .
                                      "    WHERE nodeType = 'board' " .
                                      ") ";

            $response = $dbController->query($getBoardAndDescription);

            $groupedByBoard = self::groupNodesByNodeKeys(
                array_unique(array_column($response, 'nodeKey')),
                $response
            );

            $groupedByBoard = array_values($groupedByBoard);

            $groupedByBoard = array_map(
                fn ($board) => self::getDetailsFromNodes(
                    $board, 'board',
                    ['boardName', 'boardDescription', 'board']),
                $groupedByBoard
            );

            return fwUtils::outputJsonResponse($groupedByBoard);
        }

        catch (Exception $error)
        {
            throw $error;
        }
    }

    public static function viewBoard(fwPDO $dbController, array $request)
    {
        try
        {
            fwUtils::verifyRequiredParameters(['board', 'hash'], $request);
            fwUtils::verifyHash($request['hash'], $request, fwConfigs::get('AuthSecret'));

            if ( self::doesBoardExist($dbController, $request['board']) == FALSE )
            {
                // boardId does not exist
                throw new fwServerException('000200000007',
                                            'boardId: ' . $request['board']);
            }

            $threadNodesQuery = "SELECT * FROM fwGraphNodes WHERE nodeKey IN " .
                                "( " .
                                "    SELECT edgeTo FROM fwGraphEdges " .
                                "    WHERE edgeType  = 'thread' " .
                                "    AND edgeFrom = ? " .
                                ") ";

            $threadNodes = $dbController->query($threadNodesQuery, [$request['board']]);
            $threadNodes = self::groupNodesByNodeKeys(
                array_unique(array_column($threadNodes, 'nodeKey')),
                $threadNodes
            );

            $threadDetails = (new Collection($threadNodes))
                ->map(
                    fn ($thread) => self::getDetailsFromNodes(
                        $thread,
                        'thread',
                        ['threadTitle', 'postAuthor', 'postAuthorUsername',
                         'postText', 'postDate', 'thread']
                    )              
                )
                ->map(
                    function ($thread)
                    {
                        $thread['postText'] = substr($thread['postText'], 0, 50);
                        $thread['postText'] .= '...';
                        return $thread;
                    }
                )
                ->get();

            $threadDetails = array_values($threadDetails);

            return fwUtils::outputJsonResponse($threadDetails);
        }

        catch (Exception $error)
        {
            throw $error;
        }
    }

    public static function getModerators(fwPDO $dbController, array $request)
    {
        try
        {
            fwUtils::verifyRequiredParameters(['boardId', 'hash'], $request);
            fwUtils::verifyHash($request['hash'], $request, fwConfigs::get('AuthSecret'));

            if ( self::doesBoardExist($dbController, $request['boardId']) == FALSE )
            {
                // boardId does not exist
                throw new fwServerException('000200000007');
            }

            $query = "SELECT * FROM fwGraphNodes " .
                     "WHERE nodeType = 'boardModerator' " .
                     "AND nodeKey IN " .
                     "( " .
                     "    SELECT edgeTo FROM fwGraphEdges " .
                     "    WHERE edgeType = 'boardModerator' " .
                     "    AND edgeFrom = ? " .
                     ") ";

            $response = $dbController->query($query, [$request['boardId']]);

            return fwUtils::outputJsonResponse($response);
        }

        catch (Exception $error)
        {
            throw $error;
        }
    }
}

?>
