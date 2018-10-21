<?php
/**
 * Date: 18/10/2018
 * Time: 15:04
 */

namespace App\Util\PipelineBuilder;


use App\Util\PipelineBuilder\Interfaces\Node;
use App\Util\PipelineBuilder\Interfaces\Operator;
use Kolter\Collections\ArrayList;
use Kolter\Collections\Interfaces\Collection;

abstract class AbstractOperator  implements Operator, Node
{
    /**
     * @var ArrayList
     */
    protected $fields;
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Node
     */
    protected $parent;
    /**
     * @var
     */
    protected $value;
    /**
     * @param string $name
     * @param null $value
     * @return Field
     */
    public function addField(string $name, $value = null) : Field
    {

        $field = new Field($name, $value);
        $field->setParent($this);
        $this->fields[$name] = $field;
        return $this->fields[$name];
    }


    /**
     * @param string $name
     * @return Field
     */
    public function getField(string $name) : Field
    {
        if(!$this->fields->get($name)) {
            $this->fields[$name] = new Field($name);
        }
        return $this->fields[$name];
    }



    public function __construct(string $name, $value = null)
    {
        $this->name = $name;
        $this->setValue($value);
        $this->fields = collect();
    }



    public function addFields(array $data): AbstractOperator
    {
        foreach ($data as $key=>$value){
            $this->addField($key, $value);
        }

        return $this;
    }


    public function setParent(Node $operator): Node
    {
        $this->parent = $operator;

        return $this;
    }

    public function before(): Node
    {
        return $this->parent;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Pipeline
     */
    public function setName(string $name): AbstractOperator
    {
        $this->name = $name;

        return $this;
    }

    public function getQuery()
    {
        if(!is_null($this->value)){
            if($this->value instanceof Collection){
                return $this->value->getElements();
            }
            return $this->value;
        }
        $result = collect();
        foreach ($this->fields as $key=>$value) {
            if($value instanceof Collection) {
                $result[$key] = $value->getElements();
                continue;
            }
            if($value instanceof Field){
                $result[$value->getName()] = $value->getQuery();
                continue;
            }
            $result[$key] = $value;
        }

        return $result->getElements();
    }

    public function setValue($value) : ? self
    {
        if(is_array($this->value)){
            $this->value=array_merge($this->value, $value);

            return $this;
        }
        $this->value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }


}