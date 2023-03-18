<?php

require_once(__DIR__ . "/configs.php");
require_once(__DIR__ . "/../../../vendor/autoload.php");

function viewPost ($post)
{
    $returnLink = '';
    
    if ( isset($post['thread']) )
    {
        ob_start();
?>
        <a href="viewThread.php?thread=<?php echo $post['thread']; ?>"
           class="return-link">
           [BACK TO THREAD]
        </a>
<?php
        $returnLink = ob_get_clean();
    }
    
    $post = $post['result'];
    $date = date('Y-M-d H:i:s', $post['postDate']);
    $Parsedown = new Parsedown();
    ob_start();
?>
        <article id="<?php echo $post['post']; ?>">
             <span aria-label="post metadata">
                 <b> <?php echo $post['postAuthorUsername']; ?> </b>
                 <a href="viewPost.php?post=<?php echo $post['post']; ?>">
                     <time datetime="<?php echo $date; ?>">
                         <?php echo $date; ?>
                     </time>
                 </a>
                 <?php echo $returnLink; ?>
             </span>
             <?php echo $Parsedown->text($post['postText']); ?>
        </article>
<?php
    return ob_get_clean();
}

return 'viewPost';

?>
