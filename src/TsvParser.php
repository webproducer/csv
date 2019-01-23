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
        return unescaped($this->generate($stream));
    }

    private function generate($stream): \Iterator
    {
        [$stream, $isTmp] = toStream($stream);
        try {
            while (!feof($stream)) {
                $line = rtrim(fgets($stream), "\r\n");
                yield explode($this->options->separator, $line);
            }
        } finally {
            if ($isTmp) {
                fclose($stream);
            }
        }

    }


}
