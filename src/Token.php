<?php
namespace CSV;


class Token
{

    const T_UNKNOWN = 'T_UNKNOWN';
    const T_DQUOT = 'T_DQUOT';
    const T_CR = 'T_CR';
    const T_LF = 'T_LF';
    const T_SEP = 'T_SEP';
    const T_TEXTDATA = 'T_TEXTDATA';
    const T_EOF = 'T_EOF';

    public $type = self::T_UNKNOWN;
    public $value = '';
    public $position = 0;

    /**
     * Token constructor.
     * @param string $type
     * @param string $value
     * @param int $position
     */
    public function __construct(string $type = self:: T_UNKNOWN, string $value = '', int $position = 0)
    {
        $this->type = $type;
        $this->value = $value;
        $this->position = $position;
    }

    /**
     * @param string $type
     * @param string $value
     * @param int $position
     * @return array
     */
    public static function simple(string $type = self:: T_UNKNOWN, string $value = '', int $position = 0): array
    {
        return [$type, $value, $position];
    }


}
