<?php
namespace CSV;

use function CSV\Helpers\IO\toStream;


class BuiltinParser extends BaseParser
{

    /**
     * @inheritDoc
     */
    public function parse($stream): \Iterator
    {
        [$stream, $isTmp] = toStream($stream);
        try {
            while (!feof($stream)) {
                yield fgetcsv($stream, 0, $this->options->separator);
            }
        } finally {
            if ($isTmp) {
                fclose($stream);
            }
        }
    }


}
