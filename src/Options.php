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
        return $this->makeCloneWithNewValue('separator', $separator);
    }

    public function withEncoding(int $encoding): self
    {
        return $this->makeCloneWithNewValue('encoding', $encoding);
    }

    public function withStrictModeEnabled(): self
    {
        return $this->makeCloneWithNewValue('strict', true);
    }

    public function withStrictModeDisabled(): self
    {
        return $this->makeCloneWithNewValue('strict', false);
    }

    public function withMode(string $mode): self
    {
        return $this->makeCloneWithNewValue('mode', $mode);
    }

    public function withAutoEscapeDisabled(): self
    {
        return $this->makeCloneWithNewValue('autoEscape', false);
    }

    private function makeCloneWithNewValue(string $field, $newValue): self
    {
        $c = clone $this;
        $c->$field = $newValue;
        return $c;
    }

}
