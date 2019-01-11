<?php
namespace CSV\Internal;


class StreamReader implements DataReaderInterface
{

    private $stream;

    /**
     * StreamReader constructor.
     * @param resource $stream
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException("Argument must be a valid resource");
        }
        $this->stream = $stream;
    }

    public function read(int $size = 1024): string
    {
        return fgets($this->stream, $size);
    }

    public function isEof(): bool
    {
        return feof($this->stream);
    }


}
