<?php
namespace CSV;


class Options
{
    const ENCODING_ISO = 1;
    const ENCODING_UTF = 2;

    const DEFAULT_SEPARATOR = ',';
    const DEFAULT_ENCODING = self::ENCODING_UTF;

    const MODE_RFC4180 = 'RFC4180';
    const MODE_TSV = 'TSV';

    public $separator = self::DEFAULT_SEPARATOR;
    public $encoding = self::DEFAULT_ENCODING;
    public $strict = false;
    public $mode = self::MODE_RFC4180;

    /**
     * Options constructor.
     * @param string $separator
     * @param int $encoding
     * @param string $mode
     * @param bool $strict
     */
    public function __construct(
        string $separator,
        int $encoding,
        string $mode = self::MODE_RFC4180,
        bool $strict = false
    )
    {
        $this->separator = $separator;
        $this->encoding = $encoding;
        $this->mode = $mode;
        $this->strict = $strict;
    }

    public static function withDefaults()
    {
        return new self(
            self::DEFAULT_SEPARATOR,
            self::DEFAULT_ENCODING,
            self::MODE_RFC4180,
            false
        );
    }

    public static function tsv($separator = "\t"): self
    {
        return new self(
            $separator,
            self::DEFAULT_ENCODING,
            self::MODE_TSV,
            false
        );
    }

    public static function strict(
        string $separator = self::DEFAULT_SEPARATOR,
        int $encoding = self::DEFAULT_ENCODING
    ): self
    {
        return new self($separator, $encoding, true);
    }


}
