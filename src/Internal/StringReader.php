<?php
namespace CSV\Internal;

use CSV\DataReaderInterface;

class StringReader implements DataReaderInterface
{

    private $s = '';
    private $len = 0;
    private $pos = 0;

    /**
     * StringReader constructor.
     * @param string $s
     */
    public function __construct(string $s = '')
    {
        $this->s = $s;
        $this->len = strlen($s);
    }

    public function read(int $size = 1024): string
    {
        $chunk = substr($this->s, $this->pos, $size);
        $this->pos += strlen($chunk);
        return $chunk;
    }

    public function isEof(): bool
    {
        return $this->pos === $this->len;
    }


}
