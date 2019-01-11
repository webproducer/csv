<?php

namespace CSV;

class Writer
{
    const ENCODING_ISO = 1;
    const ENCODING_UTF = 2;

    protected $fileHandle = null;
    protected $encoding = self::ENCODING_ISO;
    protected $sep = ParseTools::DEFAULT_SEPARATOR;

    /**
     * Writer constructor.
     * @param resource $output
     * @param int $encoding
     * @param string $sep
     */
    public function __construct(
        $output,
        $encoding = self::ENCODING_ISO,
        $sep = ParseTools::DEFAULT_SEPARATOR
    ) {
        $this->encoding = $encoding;
        $this->fileHandle = $output;
        $this->sep = $sep;
    }

    /**
     * @param array $values
     */
    public function addLine(array $values)
    {
        foreach ($values as $key => $value) {
            $enc = ParseTools::escapeString($value, $this->sep);
            if ($this->encoding == self::ENCODING_ISO) {
                $enc = utf8_decode($enc);
            }
            $values[$key] = $enc;
        }
        $string = implode($this->sep, $values) . "\r\n";
        fwrite($this->fileHandle, $string);
    }
}
