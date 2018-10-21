<?php
/**
 * Date: 17/10/2018
 * Time: 17:29
 */

namespace App\Util\PipelineBuilder\Interfaces;


use App\Util\PipelineBuilder\Field;

interface Operator
{
    public function addField(string $name, $value = null) : Field;
    public function getField(string $name) : Field;
}