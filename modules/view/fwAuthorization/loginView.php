<?php

require_once(__DIR__ . '/configs.php');

?>

<?php function loginGET($data = [])
{
    $isError = '';
    $divType = '';

    if ( isset($data['error']) )
    {
        $isError = 'Please make sure that your username and password is correct.';
        $divType = 'class="loginError"';
    }
    
    ob_start();
?>
<form action="login.php" <?php echo $divType; ?>>
    <span><?php echo $isError; ?></span>
    <input type="text" name="username" placeholder="username">
    <input type="password" name="passwordHash" placeholder="password">
    <input type="submit" value="LOGIN">
</form>
<?php return ob_get_clean();
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

    $query = "SELECT fwUserId FROM fwUsers WHERE username = ? AND password = ?";

    $response = $dbConnnection->query($query, [$username, $passwordHash]);

    if ( isset($response[0]['fwUserId']) )
        return $response[0]['fwUserId'];
    else
        return FALSE;
}

function callEndpoint($fwUserId, $password)
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
    $userId = getUserId($_REQUEST['username'], md5($_REQUEST['password']));

    if ( $userId == FALSE )
        return loginGET(['error' => 1]);

    $response = callEndpoint($userId);

    if ( isset($response['errorCode']) )
        return loginGET(['error' => 1]);

    else return '<h1>SUCCESS</h1>';
}
    
function loginView($data)
{
    session_start();

    switch ( $_SERVER['REQUEST_METHOD'] )
    {
        case 'GET' : return loginGET();
        case 'POST': return loginPOST();
        default    : die('INVALID HTTP METHOD');
    }
}

return 'loginView';

?>
