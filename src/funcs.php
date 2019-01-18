<?php
namespace CSV\Helpers;

use CSV\ProcessingException;

/**
 * @param \Generator $rows
 * @return \Generator|array[]
 * @throws ProcessingException
 */
function mapped(\Generator $rows): \Generator {
    if (!$rows->valid()) {
        return;
    }
    $headers = $rows->current();
    $rows->next();
    $num = 1;
    while ($rows->valid()) {
        $num++;
        $row = array_combine($headers, $rows->current());
        if ($row === false) {
            throw new ProcessingException("Error mapping row: column count mismatch in row {$num}");
        }
        yield $row;
        $rows->next();
    }
}
