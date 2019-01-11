<?php
namespace CSV\Helpers;

/**
 * @param \Generator $rows
 * @return \Generator|array[]
 */
function mapped(\Generator $rows): \Generator {
    $headers = null;
    foreach ($rows as $row) {
        if (is_null($headers)) {
            $headers = $row;
            continue;
        }
        yield array_combine($headers, $row);
    }
}
