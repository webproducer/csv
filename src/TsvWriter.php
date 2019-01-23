<?php
namespace CSV;


class TsvWriter extends BaseWriter
{

    public function makeRow(array $values): string
    {
        return implode($this->options->separator, array_map(function($value) {
            return addcslashes($value, "{$this->options->separator}\r\n");
        }, $values)) . "\n";
    }

}
