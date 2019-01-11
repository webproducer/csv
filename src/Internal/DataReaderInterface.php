<?php
namespace CSV\Internal;


interface DataReaderInterface
{

    public function read(int $size = 1024): string;

    public function isEof(): bool;

}
