<?php
namespace CSV\Async;

use Amp\Iterator;


interface ParserInterface
{

    public function parse($input): Iterator;

}
