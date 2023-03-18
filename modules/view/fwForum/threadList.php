<?php

require_once(__DIR__ . "/configs.php");

function threadList($threadData)
{
    $data = new Collection($threadData['result']);
    ob_start();
?>

<table border="1">
    <thead>
    <tr>
        <td>Title</td>
        <td>Started by</td>
        <td>Preview</td>
        <td>Date Posted</td>
    </tr>
    </thead>
    <tbody>
    <?php

     echo $data->map(function ($thread)
          {
              ob_start();
    ?>
          <tr>
              <td>
                   <a href="/viewThread.php?thread=<?php echo $thread['thread']; ?>">
                       <?php echo $thread['threadTitle']; ?>
                   </a>
              </td>
              <td> <?php echo $thread['postAuthorUsername'] ?> </td>
              <td> <?php echo $thread['postText']; ?> </td>
              <td>
                   <?php echo date("Y-M-d", $thread['postDate']); ?>
              </td>
          </tr>
    <?php
              return ob_get_clean();
          })
               ->reduce(fn ($prev, $next) => $prev .= $next, "")
               ->get();
     
    ?>
    </tbody>
</table>

<?php

     return ob_get_clean();
}

return 'threadList';

?>
