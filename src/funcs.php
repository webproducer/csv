<?php
namespace CSV\Helpers {

    use CSV\ProcessingException;

    /**
     * @param \Iterator $rows
     * @return \Generator|array[]
     * @throws ProcessingException
     */
    function mapped(\Iterator $rows): \Generator {
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

    function unescaped(\Iterator $rows): \Generator {
        foreach ($rows as $row) {
            yield array_map('stripcslashes', $row);
        }
    }

}

namespace CSV\Helpers\IO {

    use CSV\DataReaderInterface;
    use CSV\Internal\{StreamReader, StringReader};

    /**
     * @param resource|string|DataReaderInterface $stream
     * @return DataReaderInterface
     * @throws \InvalidArgumentException
     */
    function makeReader($stream): DataReaderInterface
    {
        switch (true) {
            case is_resource($stream):
                return new StreamReader($stream);
            case is_string($stream):
                return new StringReader($stream);
            case ($stream instanceof DataReaderInterface):
                return $stream;
            default:
                throw new \InvalidArgumentException("Argument must be of type resource, string or be an implementation of DataReaderInterface");
        }
    }

    /**
     * @param resource|string $src
     * @param int $maxMemUsage
     * @return array [resource $stream, bool $isTemporary]
     * @throws \InvalidArgumentException
     */
    function toStream($src, int $maxMemUsage = 10 * 1024 * 1024)
    {
        switch (true) {
            case is_resource($src):
                return [$src, false];
            case is_string($src):
                //TODO: optimize?
                $fp = makeTmpStream($maxMemUsage);
                fputs($fp, $src);
                rewind($fp);
                return [$fp, true];
            case $src instanceof DataReaderInterface:
                //TODO: optimize?
                $fp = makeTmpStream($maxMemUsage);
                while (!$src->isEof()) {
                    fputs($fp, $src->read());
                }
                rewind($fp);
                return [$fp, true];
            default:
                throw new \InvalidArgumentException(sprintf(
                    "Can't convert argument of type %s to stream",
                    gettype($src)
                ));
        }

    }

    function makeTmpStream(int $maxMemUsage = 10 * 1024 * 1024)
    {
        return fopen("php://temp/maxmemory:{$maxMemUsage}", 'r+');
    }

}


