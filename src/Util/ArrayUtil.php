<?php
/**
 * Date: 16/10/2018
 * Time: 17:53
 */

namespace App\Util;


class ArrayUtil
{
    public static function BSONtoArray($document)
    {
        $document = json_decode(json_encode($document), 1);

        return $document;
    }
}