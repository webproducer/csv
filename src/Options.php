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

    public $autoEscape = true;

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

    public static function defaults()
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

    public function withSeparator(string $separator): self
    {
        return new self($separator, $this->encoding, $this->mode, $this->strict);
    }

    public function withEncoding(int $encoding): self
    {
        return new self($this->separator, $encoding, $this->mode, $this->strict);
    }

    public function withStrictModeEnabled(bool $strict): self
    {
        return new self($this->separator, $this->encoding, $this->mode, true);
    }

    public function withStrictModeDisabled(): self
    {
        return new self($this->separator, $this->encoding, $this->mode, false);
    }

    public function withMode(string $mode): self
    {
        return new self($this->separator, $this->encoding, $mode, $this->strict);
    }

    public function withAutoEscapeDisabled(): self
    {
        $clone = new self($this->separator, $this->encoding, $this->mode, true);
        $clone->autoEscape = false;
        return $clone;
    }

}
