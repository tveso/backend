<?php
/**
 * Date: 02/01/2019
 * Time: 11:59
 */

namespace App\Pipelines;


abstract class AbstractPipeline
{
    public function pipe($array, ...$args)
    {
        foreach ($args as $key=>$value) {
            $method = $value;
            $args = [];
            if (is_array($value)) {
                $method = $value[0];
                $args = $value[1];
            }
            if (method_exists($this, $method)) {
                $pipeResult = call_user_func_array([$this, $method], $args);
                $array = array_merge($array, $pipeResult);
            }
        }

        return $array;
    }
}