<?php
/**
 * Date: 07/01/2019
 * Time: 2:38
 */

namespace App\Pipelines;


class PipelineFactory
{
    private $pipelineClasses = [
        'common' => CommonPipelines::class,
        'episode' => EpisodePipeline::class,
        'follow' => FollowPipeline::class,
        'list' => ListPipeline::class,
        'movie' => MoviePipeline::class,
        'people' => PeoplePipeline::class
    ];


    private $pipeline = [];

    public function __construct(array $pipeline = [])
    {
        $this->pipeline = $pipeline;
    }

    /**
     * @param string $type
     * @param array $args
     * @return PipelineFactory
     * @throws \Exception
     */
    public function add(string $type, ...$args) {
        if (!array_key_exists($type, $this->pipelineClasses)) {
            throw new \Exception();
        }
        $class = $this->pipelineClasses[$type];
        $class = new $class();
        /** @var AbstractPipeline $class */
        $this->pipeline = $class->pipe($this->pipeline, ...$args);

        return $this;
    }

    public function getPipeline()
    {
        return $this->pipeline;
    }



}