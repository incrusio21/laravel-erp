
<?php

namespace Erp\Foundation;

use ArrayObject;

class Dict extends ArrayObject
{
    public function __get($key)
    {
        if (isset($this[$key])) {
            return $this[$key];
        }
        throw new Exception("Attribute {$key} does not exist.");
    }

    public function __set($key, $value)
    {
        $this[$key] = $value;
    }

    public function update($input)
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $this[$key] = $value;
            }
            return $this;
        }
        throw new Exception("Expected input to be an array.");
    }

    public function copy()
    {
        return new Dict($this->getArrayCopy());
    }
}