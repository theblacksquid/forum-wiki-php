<?php

require_once(__DIR__ . "/configs.php");

function editScreen($data)
{
    $vals = [
        'fwUserId' => $data['postAuthor'], 
        'postId' => $data['post'],
        'authToken' => $data['authToken']
    ];
    
    ob_start();
?>
    <form method="POST"
          hx-post="editPost.php"
          hx-vals="<?php echo json_encode($vals); ?>">
         <textarea name="postText">
             <?php echo $data['postText']; ?>
         </textarea>
         <input type="submit" value="SUBMIT" />
    </form>
<?php
    return ob_get_clean();
}

return 'editScreen';

?>
