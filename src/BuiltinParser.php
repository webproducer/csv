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
            $num = 0;
            while (($row = fgetcsv($stream, 0, $this->options->separator)) !== false) {
                $num++;
                if ((count($row) === 1) && is_null($row[0])) {
                    if ($this->options->strict) {
                        throw new ParseException("Line {$num} is empty (empty lines are not allowed in the strict mode)");
                    }
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
