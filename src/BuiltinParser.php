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
                $row = fgetcsv($stream, 0, $this->options->separator);
                if ((count($row) === 1) && is_null($row[0])) {
                    continue;
                }
                yield $row;
            }
        } finally {
            if ($isTmp) {
                fclose($stream);
            }
        }
    }


}
