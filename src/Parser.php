<?php
namespace CSV;


class Parser
{
    const S_NEXT_FLD = 'S_NEXT_FLD';
    const S_FIRST_QUOT = 'S_FIRST_QUOT';
    const S_QUOTED_FLD = 'S_QUOTED_FLD';
    const S_UNQUOTED_FLD = 'S_UNQUOTED_FLD';

    private $options;

    private $curState = self::S_NEXT_FLD;
    private $curRow = [];
    private $isReadyToEmit = false;
    private $buf = '';
    private $columnsCnt = -1;

    public function __construct(Options $options = null)
    {
        $this->options = $options ?: Options::withDefaults();
    }

    /**
     * @todo auto-wrapper for string type
     * @todo accept only CRLF as row divider in strict mode
     * @param resource $stream
     * @return \Generator|array[]
     * @throws Exception
     * @throws ParseException
     */
    public function parse($stream): \Generator
    {
        $this->reset();
        $lexer = new Lexer($this->options->separator);
        foreach ($lexer->lex($stream) as [$type, $val, $pos]) {
            $this->handleToken($type, $val, $pos);
            if ($this->isReadyToEmit) {
                if ($this->options->strictMode) {
                    if ($this->columnsCnt < 0) {
                        $this->columnsCnt = count($this->curRow);
                    } elseif (count($this->curRow) !== $this->columnsCnt) {
                        throw new ParseException("Columns count must be constant in strict mode (position: {$pos})");
                    }
                }
                yield $this->emit();
            }
        }
    }

    private function reset()
    {
        $this->curState = self::S_NEXT_FLD;
        $this->curRow = [];
        $this->buf = '';
        $this->isReadyToEmit = false;
        $this->columnsCnt = -1;
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
            case Token::T_EOF:
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
                if ($this->options->strictMode && ($type !== Token::T_EOF)) {
                    throw new ParseException("Empty lines are prohibited in strict mode (position: {$pos})");
                }
                // else just skip
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
                $this->throwUnexpectedTokenError($type, $pos);
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
                $this->throwUnexpectedTokenError($type, $pos);
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
            case self::S_UNQUOTED_FLD:
                $this->buf.= '"';
                break;
            default:
                $this->throwUnexpectedTokenError($type, $pos);
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
                $this->throwUnexpectedTokenError($type, $pos);
        }
    }

    /**
     * @param string $type
     * @param int $pos
     * @throws ParseException
     */
    private function throwUnexpectedTokenError(string $type, int $pos)
    {
        throw new ParseException(sprintf(
            "CSV parse error: unexpected token %s at position %d",
            $type, $pos
        ));
    }

}
