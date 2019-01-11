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

    /**
     * Take a CSV line (utf-8 encoded) && returns an array
     * 'string1,string2,"string3","the ""string4"""' => array('string1', 'string2', 'string3', 'the "string4"')
     *
     * @param $string
     * @param string $separator
     * @return array
     * @throws \Exception
     */
    public static function parseString($string, $separator = self::DEFAULT_SEPARATOR)
    {
        $values = array();
        $string = str_replace("\r\n", '', $string); // eat the trailing new line, if any
        if ($string == '') {
            return $values;
        }
        $tokens = explode($separator, $string);
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            $len = strlen($token);
            $newValue = '';
            if (!self::hasStartQuote($token, $len)) {
                // No quoted token use as is
                $values[] = $token;
                continue;
            }
            // if quoted
            $token = substr($token, 1); // remove leading quote
            $len--;
            do {
                // concatenate with next token while incomplete
                $complete = self::hasValidEndQuote($token, $len);
                if ($complete) {
                    $token = substr($token, 0, -1); // remove trailing quote
                    $newValue .= $token;
                } else {
                    // incomplete, get one more token
                    $newValue .= $token;
                    $newValue .= $separator;
                    if ($i == $count - 1) {
                        throw new \Exception('Illegal unescaped quote.');
                    }
                    $token = $tokens[++$i];
                    $len = strlen($token);
                }
            } while (!$complete);
            $values[] = str_replace('""', '"', $newValue); // unescape escaped quotes;
        }
        return $values;
    }

    public static function escapeString($string, $sep = self::DEFAULT_SEPARATOR)
    {
        $string = str_replace('"', '""', $string);
        if ((strpos($string, '"') !== false) || (strpos($string, $sep) !== false) ||
            (strpos($string, "\r") !== false) || (strpos($string, "\n") !== false)) {
            $string = '"' . $string . '"';
        }
        return $string;
    }

    // checks if a string ends with an unescaped quote
    // 'string"' => true
    // 'string""' => false
    // 'string"""' => true
    // 'string\""' => true
    private static function hasValidEndQuote($token, $len)
    {
        if ($len == 0) {
            return false;
        }
        if ($len == 1) {
            return $token == '"';
        }
        if ($token[$len - 1] != '"') {
            return false;
        }
        $i = $len - 1;
        $quotesCount = 0;
        while ($i >= 0) {
            if ($token[$i] == '"') {
                $quotesCount++;
            } else {
                break;
            }
            $i--;
        }
        $slashesCount = 0;
        while ($i >= 0) {
            if ($token[$i] == '\\') {
                $slashesCount++;
            } else {
                break;
            }
            $i--;
        }
        return ($quotesCount + $slashesCount) % 2 != 0;
    }

    // very basic separator detection function
    public static function detectSeparator($filename, $separators = array(',', ';'))
    {
        $file = fopen($filename, 'r');
        $string = fgets($file);
        fclose($file);
        $matched = array();
        foreach ($separators as $separator) {
            if (preg_match("/$separator/", $string)) {
                $matched[] = $separator;
            }
        }
        return (count($matched) == 1) ? $matched[0] : null;
    }

    private static function hasStartQuote($token, $len)
    {
        return ($len > 0) && ($token[0] == '"');
    }
}
