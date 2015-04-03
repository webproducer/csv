<?php
namespace ParseTools\Csv;

class Writer
{
    const ENCODING_ISO = 1;
    const ENCODING_UTF = 2;

    protected $fileHandle = null;
    protected $encoding = self::ENCODING_ISO;

    public function __construct($filename, $mode = 'w', $encoding = self::ENCODING_ISO) {
        if ($mode != 'w' and $mode != 'a') {
            throw new \Exception('CsvWriter only accepts "w" and "a" mode.');
        }
        $this->encoding = $encoding;
        $this->fileHandle = fopen($filename, $mode);
        if (!$this->fileHandle) {
            throw new \Exception("Impossible to open file $filename.");
        }
    }

    public function __destruct() {
        $this->close();
    }

    public function addLine(array $values) {
        foreach ($values as $key => $value) {
            $enc = Parser::escapeString($value);
            if ($this->encoding == self::ENCODING_ISO) {
                $enc = utf8_decode($enc);
            }
            $values[$key] = $enc;
        }
        $string = implode(',', $values) . "\r\n";
        fwrite($this->fileHandle, $string);
    }

    // You should not have to call it unless you need to flush the
    // data from the buffer to your file explicitly before the
    // end of your script
    public function close() {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }
}
