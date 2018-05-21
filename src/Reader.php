<?php

namespace CSV;

class Reader implements \Iterator
{
    const ENCODING_ISO = 1;
    const ENCODING_UTF = 2;

    protected $fileHandle = null;
    protected $position = null;
    protected $currentLine = null;
    protected $currentArray = null;
    protected $separator = ',';
    protected $encoding = self::ENCODING_ISO;

    /**
     * Reader constructor.
     * @param resource $file
     * @param string $separator
     * @param int $encoding
     * @throws \Exception
     */
    public function __construct(
        $file,
        $separator = Parser::DEFAULT_SEPARATOR,
        $encoding = self::ENCODING_ISO
    ) {
        $this->separator = $separator;
        $this->encoding = $encoding;
        $this->fileHandle = $file;
        $this->position = 0;
        $this->readLine();
    }

    /**
     * @throws \Exception
     */
    public function rewind()
    {
        if ($this->fileHandle) {
            $this->position = 0;
            rewind($this->fileHandle);
        }

        $this->readLine();
    }

    public function current()
    {
        return $this->currentArray;
    }

    public function key()
    {
        return $this->position;
    }

    /**
     * @throws \Exception
     */
    public function next()
    {
        $this->position++;
        $this->readLine();
    }

    public function valid()
    {
        return $this->currentArray !== null;
    }

    /**
     * @throws \Exception
     */
    protected function readLine()
    {
        if (!feof($this->fileHandle)) {
            $this->currentLine = fgets($this->fileHandle);
            if ($this->encoding == self::ENCODING_ISO) {
                $this->currentLine = utf8_encode($this->currentLine);
            }
            $this->currentLine = trim($this->currentLine);
        } else {
            $this->currentLine = null;
        }
        if ($this->currentLine != '') {
            $this->currentArray = Parser::parseString($this->currentLine, $this->separator);
        } else {
            $this->currentArray = null;
        }
    }
}
