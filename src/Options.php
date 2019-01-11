<?php
namespace CSV;


class Options
{
    const ENCODING_ISO = 1;
    const ENCODING_UTF = 2;

    const DEFAULT_SEPARATOR = ',';
    const DEFAULT_ENCODING = self::ENCODING_UTF;

    public $separator = self::DEFAULT_SEPARATOR;
    public $encoding = self::DEFAULT_ENCODING;
    public $strictMode = false;

    /**
     * Options constructor.
     * @param string $separator
     * @param int $encoding
     * @param bool $strictMode
     */
    public function __construct(string $separator, int $encoding, bool $strictMode = false)
    {
        $this->separator = $separator;
        $this->encoding = $encoding;
        $this->strictMode = $strictMode;
    }

    public static function withDefaults()
    {
        return new self(
            self::DEFAULT_SEPARATOR,
            self::DEFAULT_ENCODING,
            false
        );
    }

    public static function strict(string $separator = self::DEFAULT_SEPARATOR, int $encoding = self::DEFAULT_ENCODING)
    {
        return new self($separator, $encoding, true);
    }


}
