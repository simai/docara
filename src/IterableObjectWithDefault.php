<?php

namespace Simai\Docara;

class IterableObjectWithDefault extends IterableObject
{
    public function __toString()
    {
        return $this->first() ?: '';
    }
}
