<?php
namespace CSV;

/**
 * Class ParseTools
 * @package CSV
 * @deprecated
 */
class ParseTools
{
    const DEFAULT_SEPARATOR = ',';

    public static function escapeString($string, $sep = self::DEFAULT_SEPARATOR)
    {
        $string = str_replace('"', '""', $string);
        if ((strpos($string, '"') !== false) || (strpos($string, $sep) !== false) ||
            (strpos($string, "\r") !== false) || (strpos($string, "\n") !== false)) {
            $string = '"' . $string . '"';
        }
        return $string;
    }
    
}
