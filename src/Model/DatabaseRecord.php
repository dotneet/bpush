<?php
namespace BPush\Model;

trait DatabaseRecord {

    public function offsetSet($offset, $value)
    {
        throw new \Exception('unsupported exception.');
    }

    public function offsetExists($offset)
    {
        return isset($this->{$offset});
    }

    public function offsetUnset($offset)
    {
        unset($this->{$offset});
    }


    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    public function __get($name) {
        if ( isset($this->{$name}) ) {
            return $this->{$name};
        } else {
            throw new \LogicException(sprintf('Undefined property: $%s', $name));
        }
    }

    public function __set($name, $value) {
        $this->{$name} = $value;
    }

    public function setAsProperty($data) {
        foreach ( $data as $key => $val ) {
            $this->{$key} = $val;
        }
    }

}

