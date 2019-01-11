<?php
namespace CSV;


class Parser
{

    const S_NEXT_FLD = 'S_NEXT_FLD';
    const S_FIRST_QUOT = 'S_FIRST_QUOT';
    const S_QUOTED_FLD = 'S_QUOTED_FLD';
    const S_UNQUOTED_FLD = 'S_UNQUOTED_FLD';

    private $sep;

    private $curState = self::S_NEXT_FLD;
    private $curRow = [];
    private $isReadyToEmit = false;
    private $buf = '';

    public function __construct(string $separator = ParseTools::DEFAULT_SEPARATOR)
    {
        $this->sep = $separator;
    }

    /**
     * @todo auto-wrapper for string type
     * @todo check fields count (in "strict" mode)
     * @param resource $stream
     * @return \Generator|array[]
     * @throws Exception
     * @throws ParseException
     */
    public function parse($stream): \Generator
    {
        $this->reset();
        $lexer = new Lexer($this->sep);
        foreach ($lexer->lex($stream) as [$type, $val, $pos]) {
            $this->handleToken($type, $val, $pos);
            if ($this->isReadyToEmit) {
                yield $this->emit();
            }
        }
        if ($this->curState !== self::S_NEXT_FLD) {
            $this->throwParseError($type ?? 'T_EOF', $pos ?? 0);
        }
    }

    private function reset()
    {
        $this->curState = self::S_NEXT_FLD;
        $this->curRow = [];
        $this->buf = '';
        $this->isReadyToEmit = false;
    }

    private function flushBuf()
    {
        $this->curRow[] = $this->buf;
        $this->buf = '';
    }

    private function emit(): array
    {
        try {
            return $this->curRow;
        } finally {
            $this->isReadyToEmit = false;
            $this->curRow = [];
        }
    }

    private function changeState(string $newState)
    {
//        printf("State changed: %s -> %s\n", $this->curState, $newState);
        $this->curState = $newState;
    }

    /**
     * @param string $type
     * @param string $val
     * @param int $pos
     * @throws Exception
     * @throws ParseException
     */
    private function handleToken(string $type, string $val, int $pos)
    {
//        printf(
//            "Handling token %s ('%s') at pos:%d (current state is %s)\n",
//            $type, str_replace(["\r", "\n"], ['\r', '\n'], $val), $pos, $this->curState
//        );
        switch ($type) {
            case Token::T_DQUOT:
                $this->handleQuot($type, $pos);
                break;
            case Token::T_SEP:
                $this->handleSeparator($type, $val, $pos);
                break;
            case Token::T_LF:
            case Token::T_CR:
                $this->handleLineBreak($type, $val, $pos);
                break;
            case Token::T_TEXTDATA:
                $this->handleText($type, $val, $pos);
                break;
            default:
                throw new Exception(sprintf(
                    'Unknown token %s in position %d',
                    $type, $pos
                ));
        }
    }

    /**
     * @param string $type
     * @param string $val
     * @param int $pos
     * @throws ParseException
     */
    private function handleLineBreak(string $type, string $val, int $pos)
    {
        switch ($this->curState) {
            case self::S_NEXT_FLD:
                // just skip
                break;
            case self::S_QUOTED_FLD:
                $this->buf.= $val;
                break;
            case self::S_FIRST_QUOT:
            case self::S_UNQUOTED_FLD:
                $this->flushBuf();
                $this->isReadyToEmit = true;
                $this->changeState(self::S_NEXT_FLD);
                break;
            default:
                $this->throwParseError($type, $pos);
        }
    }

    /**
     * @param string $type
     * @param string $val
     * @param int $pos
     * @throws ParseException
     */
    private function handleSeparator(string $type, string $val, int $pos)
    {
        switch ($this->curState) {
            case self::S_NEXT_FLD:
                $this->flushBuf();
                break;
            case self::S_QUOTED_FLD:
                $this->buf.= $val;
                break;
            case self::S_FIRST_QUOT:
            case self::S_UNQUOTED_FLD:
                $this->flushBuf();
                $this->changeState(self::S_NEXT_FLD);
                break;
            default:
                $this->throwParseError($type, $pos);
        }
    }

    /**
     * @param string $type
     * @param int $pos
     * @throws ParseException
     */
    private function handleQuot(string $type, int $pos)
    {
        switch ($this->curState) {
            case self::S_NEXT_FLD:
                $this->changeState(self::S_QUOTED_FLD);
                break;
            case self::S_FIRST_QUOT:
                $this->buf.= '"';
                $this->changeState(self::S_QUOTED_FLD);
                break;
            case self::S_QUOTED_FLD:
                $this->changeState(self::S_FIRST_QUOT);
                break;
            default:
                $this->throwParseError($type, $pos);
        }
    }

    /**
     * @param string $type
     * @param string $val
     * @param int $pos
     * @throws ParseException
     */
    private function handleText(string $type, string $val, int $pos)
    {
        switch ($this->curState) {
            case self::S_NEXT_FLD:
                $this->buf.= $val;
                $this->changeState(self::S_UNQUOTED_FLD);
                break;
            case self::S_QUOTED_FLD:
            case self::S_UNQUOTED_FLD:
                $this->buf.= $val;
                break;
            default:
                $this->throwParseError($type, $pos);
        }
    }

    /**
     * @param string $type
     * @param int $pos
     * @throws ParseException
     */
    private function throwParseError(string $type, int $pos)
    {
        throw new ParseException(sprintf(
            "CSV parse error: unexpected token %s at position %d",
            $type, $pos
        ));
    }

}
