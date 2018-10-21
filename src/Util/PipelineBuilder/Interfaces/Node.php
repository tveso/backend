<?php
/**
 * Date: 17/10/2018
 * Time: 17:56
 */

namespace App\Util\PipelineBuilder\Interfaces;


interface Node
{
    public function setParent(Node $operator) : self;
    public function before(): Node;
    public function getQuery();
}