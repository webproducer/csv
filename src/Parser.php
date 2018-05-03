<?php

namespace CSV;

class Parser
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
                $token = str_replace('\\""', '\\"', $token, $isReplacedSlashes);
                $token = str_replace('""', '"', $token, $isReplacedQuotes); // unescape escaped quotes
                if ($complete) {
                    if ($isReplacedSlashes || $isReplacedQuotes) {
                        $len = strlen($token);
                    }
                    if ((($len == 1) && ($token == '"')) || isset($token[-2]) && ($token[-2] != '\\')) {
                        $token = substr($token, 0, -1); // remove trailing quote
                    }
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
            $values[] = $newValue;
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
        if ($len == 1) {
            return $token == '"';
        }
        while ($len > 1 && $token[$len - 1] == '"' && $token[$len - 2] == '"') {
            // there is an escaped quote at the end
            $len -= 2; // strip the escaped quote at the end
        }
        if ($len == 0) {
            // the string was only some escaped quotes
            return false;
        } elseif (($token[$len - 1] == '"') || ($token[$len - 1] == "\\")) {
            // the last quote was not escaped
            return true;
        } else {
            // was not ending with an unescaped quote
            return false;
        }
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
        return ($len == 0) || ($token[0] == '"');
    }
}
