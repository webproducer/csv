<?php
namespace CSV\Internal;

use CSV\Options;

class Lexer
{
    const READ_SIZE = 1024;

    private $sep;

    public function __construct(string $separator = Options::DEFAULT_SEPARATOR)
    {
        $this->sep = $separator;
    }

    /**
     * @param DataReaderInterface $stream
     * @return \Generator|Token[]
     */
    public function lex(DataReaderInterface $stream): \Generator
    {
        $pos = 0;
        $buf = '';
        $map = array_merge(self::getTokenMap(), [
            $this->sep => Token::T_SEP
        ]);
        $brklist = implode(array_keys($map));
        while (!$stream->isEof()) {
            $data = $stream->read(self::READ_SIZE);
            $len = strlen($data);
            while ($len > 0) {
                if (($remainStr = strpbrk($data, $brklist)) === false) {
                    $buf.= $data;
                    break;
                }
                $lenRemain = strlen($remainStr);
                $current = $buf . substr($data, 0, $len - $lenRemain);
                if ($current !== '') {
                    yield Token::simple(Token::T_TEXTDATA, $current, $pos);
                    $buf = '';
                    $pos += strlen($current);
                }
                $c = $remainStr{0};
                yield Token::simple($map[$c], $c, $pos);
                $data = substr($remainStr, 1);
                $len = $lenRemain - 1;
                $pos++;
            }
        }
        if ($buf !== '') {
            yield Token::simple(Token::T_TEXTDATA, $buf, $pos);
            $pos += strlen($buf);
        }
        yield Token::simple(Token::T_EOF, '', $pos);
    }

    protected static function getTokenMap(): array
    {
        return [
            "\r" => Token::T_CR,
            "\n" => Token::T_LF,
            '"' => Token::T_DQUOT
        ];
    }

}
