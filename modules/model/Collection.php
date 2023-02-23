<?php

final class Collection
{
    private $data = [];

    public function __construct($initialData)
    {
        $this->data = $initialData;
    }

    public function get()
    {
        return $this->data;
    }

    public function map(callable $callback)
    {
        $result = array_map($callback, $this->data);

        return new Collection($result);
    }

    public function filter(callable $predicate)
    {
        $result = array_filter($this->data, $predicate);
        $result = array_values($result);
        
        return new Collection($result);
    }

    public function reduce(callable $callback, $accumulator = NULL)
    {
        if ( $accumulator == NULL )
        {
            $result = array_reduce($this->data, $callback);
        }

        else
        {
            $result = array_reduce($this->data, $callback, $accumulator);
        }

        return new Collection($result);
    }
}

?>
