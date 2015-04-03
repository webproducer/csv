<?php
namespace ParseTools\Csv;

class Reader implements \Iterator
{
    const ENCODING_ISO = 1;
    const ENCODING_UTF = 2;

    protected $fileHandle = null;
    protected $position = null;
    protected $filename = null;
    protected $currentLine = null;
    protected $currentArray = null;
    protected $separator = ',';
    protected $encoding = self::ENCODING_ISO;


    public function __construct($filename, $separator = ',', $encoding = self::ENCODING_ISO) {
        $this->separator = $separator;
        $this->encoding = $encoding;
        $this->fileHandle = fopen($filename, 'r');
        if (!$this->fileHandle) {
            return;
        }
        $this->filename = $filename;
        $this->position = 0;
        $this->_readLine();
    }

    public function __destruct() {
        $this->close();
    }

    // You should not have to call it unless you need to
    // explicitly free the file descriptor
    public function close() {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }

    public function rewind() {
        if ($this->fileHandle) {
            $this->position = 0;
            rewind($this->fileHandle);
        }

        $this->_readLine();
    }

    public function current() {
        return $this->currentArray;
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        $this->position++;
        $this->_readLine();
    }

    public function valid() {
        return $this->currentArray !== null;
    }

    protected function _readLine() {
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
