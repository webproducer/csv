<?php
namespace CSV;


class Writer
{
    protected $fileHandle = null;
    protected $options;

    /**
     * Writer constructor.
     * @param resource $output
     * @param Options|null $options
     */
    public function __construct(
        $output,
        Options $options = null
    ) {
        $this->fileHandle = $output;
        $this->options = $options ?: Options::withDefaults();
    }

    /**
     * @param array $values
     */
    public function addLine(array $values)
    {
        foreach ($values as $key => $value) {
            $enc = $this->escapeString($value);
            if ($this->options->encoding == Options::ENCODING_ISO) {
                $enc = utf8_decode($enc);
            }
            $values[$key] = $enc;
        }
        $string = implode($this->options->separator, $values) . "\r\n";
        fwrite($this->fileHandle, $string);
    }

    protected function escapeString($string)
    {
        $string = str_replace('"', '""', $string);
        if ((strpos($string, '"') !== false) ||
            (strpos($string, $this->options->separator) !== false) ||
            (strpos($string, "\r") !== false) ||
            (strpos($string, "\n") !== false)) {
            $string = '"' . $string . '"';
        }
        return $string;
    }
}
