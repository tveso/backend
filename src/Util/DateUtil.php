<?php
/**
 * Date: 06/10/2018
 * Time: 22:20
 */

namespace App\Util;


class DateUtil
{


    public static function getDateFormated(\DateTime $date = null, $format = 'Y-m-d'){
        return $date->format($format);
    }
}