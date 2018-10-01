<?php
/**
 * Date: 28/09/2018
 * Time: 16:27
 */

namespace App\Entity;


use ArrayAccess;
use MongoDB\Model\BSONDocument;

class Entity implements ArrayAccess
{

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function __call($name, $arguments)
    {
        if(!method_exists($this,$name)){
            $regex = "/(get|set)(.*)/";
            if(preg_match_all($regex, $name, $mathes)){
                $getOrSet = $mathes[1][0];
                $getProperty = strtolower($mathes[2][0]);
                if($getOrSet === 'get'){
                    return $this->get($getProperty, true);
                }
                return $this->set($getProperty, $arguments[0], true);
            }
        }
        return $this->{$name}($arguments);
    }

    public function get(string $property, bool $upsert = true)
    {
        $result = null;
        if(array_key_exists($property, $this->data)) {
            $result = $this->data[$property];
            if(is_array($result)){
                return new Entity($result);
            }
            if($result instanceof \IteratorAggregate) {
                return new Entity(iterator_to_array($result));
            }

            return $result;
        }
        if($upsert) {
            $this->data[$property] = [];

            return new Entity([]);
        }

        return $result;
    }
    public function set(string $property, $value = null, $upsert = true)
    {
        $result = null;
        if(array_key_exists($property, $this->data)) {
            $this->data[$property] = $value;

            return $this;
        }
        if($upsert) {
            $this->data[$property] = $value;

            return [];
        }

        return $this;
    }


    public function getArray()
    {
        return $this->data;
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @param array $data
     * @return Entity
     */
    public function setData(array $data): Entity
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}