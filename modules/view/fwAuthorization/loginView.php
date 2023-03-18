<?php

require_once(__DIR__ . '/configs.php');

?>

<?php function loginGET($data = [])
{
    $isError = '';
    $divType = '';

    if ( isset($data['error']) )
    {
        $isError = $data['error'];
        $divType = 'class="loginError"';
    }
    
    ob_start();
?>
<form action="login.php" <?php echo $divType; ?> method="POST"
      hx-post="login.php"
      class="">
    <span class="w3-red"><?php echo $isError; ?></span>
    <input type="text" name="username" placeholder="username">
    <input type="password" name="passwordHash" placeholder="password">
    <input type="submit" value="LOGIN">
    <a href="/register.php">[REGISTER]</a>
</form>
<?php return ob_get_clean();
}
?>

<?php function alreadyLoggedIn($fwUserId)
{
    $dbConnnection = new fwPDO(
        fwConfigs::get('DBUser'),
        fwConfigs::get('DBPassword'),
        'fwAuthorization'
    );
    
    $username = $dbConnnection->query(
        "SELECT username FROM fwUsers WHERE fwUserId = ?",
        [$fwUserId]
    );

    $username = $username[0]['username'];
    
    ob_start();
?>
<div>
     <p>Welcome, <?php echo $username; ?>! [<a href="logout.php">LOGOUT</a>]</p>
</div>
<?php
    return ob_get_clean();
}
?>
    
<?php

function getUserId($username, $passwordHash)
{
    $dbConnnection = new fwPDO(
        fwConfigs::get('DBUser'),
        fwConfigs::get('DBPassword'),
        'fwAuthorization'
    );

    $query = "SELECT fwUserId FROM fwUsers WHERE username = ? AND passwordHash = ?";

    $response = $dbConnnection->query($query, [$username, $passwordHash]);

    if ( isset($response[0]['fwUserId']) )
        return $response[0]['fwUserId'];
    else
        return FALSE;
}

function callLoginEndpoint($fwUserId, $password)
{
    $_REQUEST['fwUserId'] = $fwUserId;
    $_REQUEST['passwordHash'] = md5($password);
    $_REQUEST['hash'] = fwUtils::generateHash($_REQUEST, fwConfigs::get('AuthSecret'));

    ob_start();
    require_once(__DIR__ . '/../../controller/fwAuthorization/loginUser.php');
    return ob_get_clean();
}
    
function loginPOST()
{
    $userId = getUserId($_REQUEST['username'], md5($_REQUEST['passwordHash']));

    if ( $userId == FALSE )
        return loginGET(['error' => 'Username not found']);

    $response = callLoginEndpoint($userId, $_REQUEST['passwordHash']);
    $response = json_decode($response, TRUE);

    fwUtils::debugLog($response);
    
    if ( isset($response['errorCode']) )
        return loginGET(['error' => $response['errorMessage']]);

    else
    {
        $_SESSION['authToken'] = $response['result']['authToken'];
        header('Location: /login.php');
    };
}
    
function loginView($data)
{
    session_start();

    if ( isset($_SESSION['authToken']) && fwUtils::verifyAuthToken($_SESSION['authToken']) )
    {
        $fwUserId = (explode('|', $_SESSION['authToken']))[0];
        return alreadyLoggedIn($fwUserId);
    }

    switch ( $_SERVER['REQUEST_METHOD'] )
    {
        case 'GET' : return loginGET();
        case 'POST': return loginPOST();
        default    : die('INVALID HTTP METHOD');
    }
}

return 'loginView';

?>
