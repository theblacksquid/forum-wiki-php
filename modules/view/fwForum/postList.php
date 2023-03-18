<?php

require_once(__DIR__ . "/configs.php");
require_once(__DIR__ . "/../../../vendor/autoload.php");

function postList($postData)
{
    $data = new Collection($postData['result']);

    return $data
        ->map(function ($post)
        {
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
                 </span>
                 <?php echo $Parsedown->text($post['postText']); ?>
            </article>
<?php
            return ob_get_clean();
        })
        ->reduce(fn ($prev, $next) => $prev .= $next, "")
        ->get();
}

return 'postList';
?>
