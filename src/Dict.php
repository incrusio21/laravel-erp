<?php

namespace Erp;

class Dict extends ArrayObject
{
    public function __get($key)
    {
        return $this[$key];
    }

    public function __set($key, $value)
    {
        $this[$key] = $value;
    }

    public function update($data)
    {
        foreach ($data as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }

    public function copy()
    {
        return new self($this->getArrayCopy());
    }
}