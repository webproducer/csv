<?php
namespace CSV;


class Lexer
{
    const READ_SIZE = 1024;

    private $sep;

    public function __construct(string $separator = ParseTools::DEFAULT_SEPARATOR)
    {
        $this->sep = $separator;
    }

    /**
     * @param resource $stream
     * @return \Generator|Token[]
     */
    public function lex($stream): \Generator
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException(sprintf(
                'Lexer can read only resources (%s given)',
                gettype($stream)
            ));
        }
        $pos = -1;
        $cur = -1;
        $buf = '';
        $map = array_merge(self::getTokenMap(), [
            $this->sep => Token::T_SEP
        ]);
        while (!feof($stream)) {
            $data = fgets($stream, self::READ_SIZE);
            $len = strlen($data);
            for ($i=0; $i<$len; $i++) {
                $c = $data{$i};
                $pos++;
                if (isset($map[$c])) {
                    if (!empty($buf)) {
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
        if (!empty($buf)) {
            yield Token::simple(Token::T_TEXTDATA, $buf, $cur);
        }
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