<?php
namespace CSV;

use function CSV\Helpers\IO\toStream;
use function CSV\Helpers\unescaped;


class TsvParser extends BaseParser
{

    /**
     * @inheritDoc
     */
    public function parse($stream): \Iterator
    {
        $rows = $this->generate($stream);
        return $this->options->autoEscape ? unescaped($rows) : $rows;
    }

    /**
     * @param $stream
     * @return \Iterator
     * @throws ParseException
     */
    private function generate($stream): \Iterator
    {
        [$stream, $isTmp] = toStream($stream);
        try {
            $num = 0;
            while (!feof($stream)) {
                $line = rtrim(fgets($stream), "\r\n");
                $num++;
                if (empty($line)) {
                    if ($this->options->strict) {
                        throw new ParseException("Line {$num} is empty (empty lines are not allowed in the strict mode)");
                    }
                    continue;
                }
                yield explode($this->options->separator, $line);
            }
        } finally {
            if ($isTmp) {
                fclose($stream);
            }
        }

    }


}
