<?php
namespace CSV\Internal;


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
        $pos = -1;
        $cur = -1;
        $buf = '';
        $map = array_merge(self::getTokenMap(), [
            $this->sep => Token::T_SEP
        ]);
        while (!$stream->isEof()) {
            $data = $stream->read(self::READ_SIZE);
            $len = strlen($data);
            for ($i=0; $i<$len; $i++) {
                $c = $data{$i};
                $pos++;
                if (isset($map[$c])) {
                    if ($buf !== '') {
                        yield Token::simple(Token::T_TEXTDATA, $buf, $cur);
                        $buf = '';
                        $cur = -1;
                    }
                    yield Token::simple($map[$c], $c, $pos);
                    continue;
                }
                if ($cur < 0) {
                    $cur = $pos;
                }
                $buf.= $c;
            }
        }
        if ($buf !== '') {
            yield Token::simple(Token::T_TEXTDATA, $buf, $cur);
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
