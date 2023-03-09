<?php

require_once(__DIR__ . '/config.php');

class fwForum
{
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

    private static function groupNodesByPost($postKeys, $postDataArray)
    {
        $dataProcessPipe = new Collection($postDataArray);
        $postKeysMap = new Collection($postKeys);

        return $postKeysMap->map(
            fn ($key) => $dataProcessPipe->filter(
                fn ($data) => $data['nodeKey'] == $key
            )->get()
        )->get();
    }

    private static function getPostDetails($postData)
    {
        $data = new Collection($postData);
        
        $filter = fn ($node) => in_array(
                    $node['nodeType'],
                    ["postAuthor", "postDate", "postText", "post"],
                    TRUE);

        $map = fn ($node) => $node['nodeType'] == 'post' ?
                             ['post', $node['nodeKey']] :
                             [$node['nodeType'], $node['nodeMeta']];
        
        $reduce = function ($prev, $next) { $prev[$next[0]] = $next[1]; return $prev; };
        
        return $data->filter($filter)
                    ->map($map)
                    ->reduce($reduce, array())
                    ->get();
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
            fwUtils::verifyAuthToken($request['authToken']);

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
                        "('threadVisibility', :hash, :threadVisibility)";

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
                                 "VALUES ('post', ?, ?, 0)";

            $dbController->execute($insertInitialPost, [$request['hash'], $request['hash']]);

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

        $groupedByPost = self::groupNodesByPost($postKeys, $postData);
        $groupedByPost = array_map(
            fn ($post) => self::getPostDetails($post),
            $groupedByPost);

        return fwUtils::outputJsonResponse($groupedByPost);
    }
    
    public static function deleteThread(fwPDO $dbController, array $request)
    {}

    public static function newPost(fwPDO $dbController, array $request)
    {
        try
        {
            fwUtils::verifyRequiredParameters(
                ['fwUserId', 'authToken', 'threadId', 'postText', 'hash'],
                $request
            );

            fwUtils::verifyHash($request['hash'], $request, fwConfigs::get('AuthSecret'));

            fwUtils::verifyAuthToken($request['authToken']);

            if ( strlen($request['postText']) > fwConfigs::get('MaxPostLength') )
            {
                // post content too long
                throw new fwServerException('000200000000');
            }

            $nodeInsert = "INSERT INTO fwGraphNodes (nodeType, nodeKey, nodeMeta) ".
                          "VALUES " .
                          "('post', :hash, ''), " .
                          "('postAuthor', :hash, :postAuthor), " .
                          "('postDate', :hash, UNIX_TIMESTAMP(NOW())), " .
                          "('postText', :hash, :postText) ";

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
                ['fwUserId', 'authToken', 'threadId', 'postText', 'hash'],
                $request
            );

            fwUtils::verifyHash($request['hash'], $request, fwConfigs::get('AuthSecret'));

            fwUtils::verifyAuthToken($request['authToken']);

            
        }

        catch (Exception $error)
        {
            throw $error;
        }
    }

    public static function deletePost(fwPDO $dbController, array $request)
    {}

    public static function addBoard(fwPDO $dbController, array $request)
    {}

    public static function addBoardModerator(fwPDO $dbController, array $request)
    {}
}

?>
