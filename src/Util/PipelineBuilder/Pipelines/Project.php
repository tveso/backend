<?php
/**
 * Date: 17/10/2018
 * Time: 17:23
 */

namespace App\Util\PipelineBuilder\Pipelines;


use App\Util\PipelineBuilder\Field;
use App\Util\PipelineBuilder\Pipeline;

class Project extends Pipeline
{

    public function __construct()
    {
        $name = '$project';
        parent::__construct($name);
    }


}