<?php
namespace CSV;


interface WriterInterface
{

    /**
     * @param iterable $rows
     * @param resource|null $stream - Write to given resource, or in internal buffer (if null)
     * @return int - Count of written rows
     * @throws WriteException
     */
    public function write(iterable $rows, $stream = null): int;

    /**
     * Return contents of the internal buffer
     *
     * @return string
     */
    public function getContents(): string;

}
