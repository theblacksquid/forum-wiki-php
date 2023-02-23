<?php

require_once(__DIR__ . "/./fwTestingFramework.php");
require_once(__DIR__ . "/../modules/model/Collection.php");

class tests extends fwTestingFramework
{
    public static function main()
    {
        try
        {
            $testData1 = new Collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        
            $addOne = $testData1->map(fn ($x) => $x + 1)->get();
            self::assertEquals($addOne, [2, 3, 4, 5, 6, 7, 8, 9, 10, 11]);

            $evenOnly = $testData1->filter(fn ($x) => ($x % 2) == 0)->get();
            self::assertEquals($evenOnly, [2, 4, 6, 8, 10]);
        
            $reduce = $testData1->reduce(fn ($x, $y) => $x + $y, 0)->get();
            self::assertEquals($reduce, 55);
        }

        catch (fwServerException $error)
        {
            fwServerException::outputJsonError($error->getCode());
        }
    }
}

$execute = new tests();

$execute->main();
echo "PASS\n";
?>
