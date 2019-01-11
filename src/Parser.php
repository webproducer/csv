<?php
namespace CSV;

use CSV\Internal\{DataReaderInterface, Lexer, StreamReader, StringReader, Token};


class Parser
{
    const S_NEXT_ROW = 'S_NEXT_ROW';
    const S_NEXT_FLD = 'S_NEXT_FLD';
    const S_FIRST_QUOT = 'S_FIRST_QUOT';
    const S_QUOTED_FLD = 'S_QUOTED_FLD';
    const S_UNQUOTED_FLD = 'S_UNQUOTED_FLD';
    const S_CARRIAGE_RETURNED = 'S_CARRIAGE_RETURNED';

    private $options;

    private $curState = self::S_NEXT_ROW;
    private $curRow = [];
    private $isReadyToEmit = false;
    private $buf = '';
    private $columnsCnt = -1;

    public function __construct(Options $options = null)
    {
        $this->options = $options ?: Options::withDefaults();
    }

    /**
     * @param resource|string|DataReaderInterface $stream
     * @return \Generator|array[]
     * @throws Exception
     * @throws ParseException
     */
    public function parse($stream): \Generator
    {
        switch (true) {
            case is_resource($stream):
                $stream = new StreamReader($stream);
                break;
            case is_string($stream):
                $stream = new StringReader($stream);
                break;
            case ($stream instanceof DataReaderInterface):
                // will be used as is
                break;
            default:
                throw new \InvalidArgumentException("Argument must be of type resource, string or be an implementation of DataReaderInterface");
        }
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
        $this->curState = self::S_NEXT_ROW;
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
                $this->handleQuot($type, $val, $pos);
                break;
            case Token::T_SEP:
                $this->handleSeparator($type, $val, $pos);
                break;
            case Token::T_CR:
                $this->handleCarriageReturn($type, $val, $pos);
                break;
            case Token::T_LF:
                $this->handleLineBreak($type, $val, $pos);
                break;
            case Token::T_EOF:
                $this->handleEof($type, $pos);
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
     * @param $val
     * @param int $pos
     * @throws ParseException
     */
    private function handleCarriageReturn(string $type, $val, int $pos)
    {
        switch ($this->curState) {
            case self::S_QUOTED_FLD:
                $this->buf.= $val;
                break;
            case self::S_NEXT_ROW:
            case self::S_NEXT_FLD:
            case self::S_FIRST_QUOT:
            case self::S_UNQUOTED_FLD:
                $this->changeState(self::S_CARRIAGE_RETURNED);
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
    private function handleLineBreak(string $type, string $val, int $pos)
    {
        switch ($this->curState) {
            case self::S_NEXT_ROW:
                if ($this->options->strictMode) {
                    throw new ParseException("Empty lines are prohibited in strict mode (position: {$pos})");
                }
                // else just skip
                break;
            case self::S_QUOTED_FLD:
                $this->buf.= $val;
                break;
            case self::S_CARRIAGE_RETURNED:
            case self::S_FIRST_QUOT:
            case self::S_NEXT_FLD:
            case self::S_UNQUOTED_FLD:
                if ($this->options->strictMode && ($this->curState !== self::S_CARRIAGE_RETURNED)) {
                    throw new ParseException("You should use CRLF as line separator in strict mode (position: {$pos})");
                }
                $this->flushBuf();
                $this->isReadyToEmit = true;
                $this->changeState(self::S_NEXT_ROW);
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
    private function handleEof(string $type, int $pos)
    {
        switch ($this->curState) {
            case self::S_NEXT_ROW:
                break;
            case self::S_NEXT_FLD:
                $this->flushBuf();
                $this->isReadyToEmit = true;
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
            case self::S_NEXT_ROW:
                $this->flushBuf();
                $this->changeState(self::S_NEXT_FLD);
                break;
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
     * @param string $val
     * @param int $pos
     * @throws ParseException
     */
    private function handleQuot(string $type, string $val, int $pos)
    {
        switch ($this->curState) {
            case self::S_NEXT_ROW:
            case self::S_NEXT_FLD:
                $this->changeState(self::S_QUOTED_FLD);
                break;
            case self::S_FIRST_QUOT:
                $this->buf.= $val;
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
            case self::S_NEXT_ROW:
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
