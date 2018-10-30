<?php
/**
 * Date: 17/10/2018
 * Time: 17:41
 */

namespace App\Tests\PipelineBuilder;


use App\Tests\AbstractTest;
use App\Util\PipelineBuilder\PipelineBuilder;

class PipelineBuilderTest extends AbstractTest
{
    function testFindAllPeople()
    {
        $pipelines = new PipelineBuilder();
        $pipe = $pipelines->addPipe('$project')->addFields(['key'=> 1, '_id' => 1, 'sofa' => 0]);
        $let = $pipe->addField('let');
        $let->addField('let', 'lol');
    }

    function testAdvance()
    {
        $query['pipelines']= [
            ['$match'=> ['_id' => ['$in' => [12,1243,434]]]]
        ];
        $pb = new PipelineBuilder();


        $query['sort'] = 'popularity';
    }
}