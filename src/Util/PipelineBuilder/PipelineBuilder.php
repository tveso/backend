<?php
/**
 * Date: 17/10/2018
 * Time: 16:52
 */

namespace App\Util\PipelineBuilder;


use App\Util\PipelineBuilder\Interfaces\Node;

class PipelineBuilder implements Node
{
    /**
     * @var \Kolter\Collections\ArrayList
     */
    private $pipelines;

    public function __construct()
    {
        $this->pipelines = collect();
    }

    public function addPipe(string $name, $value = null) : Pipeline
    {
        $pipeline = new Pipeline($name, $value);
        $pipeline->setParent($this);
        $this->pipelines[] = $pipeline;

        return $pipeline;
    }

    /**
     * @param string $name
     * @param int $index
     * @return Pipeline
     */
    public function getPipe(string $name, $val = null, int $index = 0) : Pipeline
    {
        $counter = 0;
        /** @var Pipeline $value */
        foreach ($this->pipelines as $key=> $value) {
            if($value->getName() === $name) {
                if($counter === $index) {
                    return $value;
                }
                ++$counter;
            }
        }
        return $this->addPipe($name, $val);
    }

    public function setParent(Node $operator): Node
    {
        return null;
    }

    public function before(): Node
    {
        return null;
    }

    public function order(array $pipelines)
    {
        /** @param Pipeline $a
         * @param Pipeline $b
         */
        $this->pipelines->sortBy()->sort(
            function ($a, $b) use($pipelines) {
                $aValue = 1;
                $bValue = 1;
                $aName = $a->getName();
                $bName = $b->getName();
                if(isset($pipelines[$aName])){
                    $aValue = $pipelines[$aName];
                }
                if(isset($pipelines[$bName])){
                    $bValue = $pipelines[$bName];
                }
                return $bValue - $aValue;
        });

        return $this;
    }

    public function getQuery(): array
    {
        $result = collect();
        foreach ($this->pipelines as $key=>$value ){
            $result[$key] = [$value->getName() => $value->getQuery()];
        }

        return $result->getElements();
    }
}