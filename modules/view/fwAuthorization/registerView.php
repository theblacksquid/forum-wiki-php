<?php

require_once(__DIR__ . '/../../model/fwConfigs.php');

?>

<?php function registerGetMethodTemplate()
{
    ob_start();
?>

<form method='POST' action='register.php'
      id ="registration-form"
      hx-boost="true"
      hx-select="main"
      hx-target="main">
     <input type='text' name='requestedUsername' placeholder='Username'>
     <input type='password' name='password' placeholder='Password'>
     <input type='submit' value='REGISTER'>
</form>
        
<?php return ob_get_clean();
}

?>

<?php function callEndpoint()
{
    $_REQUEST['password'] = md5($_REQUEST['password']);
    $_REQUEST['hash'] = fwUtils::generateHash($_REQUEST, fwConfigs::get('AuthSecret'));
    ob_start();
    require_once(__DIR__ . '/../../controller/fwAuthorization/registerUser.php');
    return ob_get_clean();
}

?>

<?php function registerPostTemplate()
{
    $apiCallResult = json_decode(callEndpoint(), TRUE);

    if ( isset($apiCallResult['errorCode']) )
    {
        return '<p>' . $apiCallResult['errorMessage'] . '<p>';
    }

    else
    {
        $forumName = fwConfigs::get('ForumName');

        return '<p> Thank you for joining ' . $forumName . '! Have a nice day.</p>';
    }
}

?>

<?php function registerView($data)
{
    if ( $_SERVER['REQUEST_METHOD'] == 'GET')
    {
        return registerGetMethodTemplate();
    }

    else return registerPostTemplate();
}

?>

<?php return 'registerView'; ?>
