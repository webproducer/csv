<?php
namespace CSV;

interface ParserInterface
{

    /**
     * @param resource|string|DataReaderInterface $stream
     * @return \Generator|array[]
     * @throws Exception
     * @throws ParseException
     */
    public function parse($stream): \Iterator;

}
