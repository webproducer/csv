<?php
namespace CSV;


class RfcWriter extends BaseWriter
{

    public function makeRow(array $values): string
    {
        foreach ($values as $key => $value) {
            $enc = $this->escapeString($value);
            if ($this->options->encoding == Options::ENCODING_ISO) {
                $enc = utf8_decode($enc);
            }
            $values[$key] = $enc;
        }
        return implode($this->options->separator, $values) . "\r\n";
    }

    protected function escapeString($string)
    {
        $string = str_replace('"', '""', $string);
        if ((strpos($string, '"') !== false) ||
            (strpos($string, $this->options->separator) !== false) ||
            (strpos($string, "\r") !== false) ||
            (strpos($string, "\n") !== false))
        {
            $string = '"' . $string . '"';
        }
        return $string;
    }

}
