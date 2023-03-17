<?php

require_once(__DIR__ . "/configs.php");

function boardList($boardData)
{
    $data = new Collection($boardData['result']);
    ob_start();
?>

<table>
    <thead>
    <tr><td>Board Name</td><td>Description</td></tr>
    </thead>
    <tbody>
    <?php

     echo $data->map(function ($board)
          {
              ob_start();
    ?>
          <tr>
              <td>
                   <a href="/viewBoard.php?board=<?php echo $board['board']; ?>">
                       <?php echo $board['boardName']; ?>
                   </a>
              </td>
              <td> <?php echo $board['boardDescription'] ?> </td>
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

return 'boardList';
?>
