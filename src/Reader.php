<?php

namespace CSV;

class Reader implements \Iterator
{
    const ENCODING_ISO = 1;
    const ENCODING_UTF = 2;

    protected $fileHandle = null;
    protected $position = 0;
    protected $currentLine = false;
    protected $separator = ',';
    protected $encoding = self::ENCODING_ISO;

    /**
     * Reader constructor.
     * @param resource $file
     * @param string $separator
     * @param int $encoding
     */
    public function __construct(
        $file,
        $separator = ParseTools::DEFAULT_SEPARATOR,
        $encoding = self::ENCODING_ISO
    ) {
        $this->separator = $separator;
        $this->encoding = $encoding;
        $this->fileHandle = $file;
        $this->readLine();
    }

    public function rewind()
    {
        if ($this->fileHandle) {
            $this->position = 0;
            rewind($this->fileHandle);
            $this->readLine();
        }
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public function current()
    {
        if ($this->encoding == self::ENCODING_ISO) {
            $this->currentLine = utf8_encode($this->currentLine);
        }
        $this->currentLine = trim($this->currentLine);
        if ($this->currentLine == '') {
            return null;
        }
        try {
            return ParseTools::parseString($this->currentLine, $this->separator);
        } catch (\Exception $e) {
            throw new \Exception("{$e->getMessage()} ({$this->currentLine})", $e->getCode(), $e);
        }
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->position++;
        $this->readLine();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->currentLine !== false;
    }

    protected function readLine()
    {
        $this->currentLine = fgets($this->fileHandle);
    }
}
