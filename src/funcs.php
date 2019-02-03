<?php
namespace CSV\Helpers {

    use Amp\Iterator as AsyncIterator;
    use Amp\Producer;
    use CSV\IOException;
    use CSV\Options;
    use CSV\Parser;
    use CSV\ProcessingException;

    /**
     * @param \Iterator|AsyncIterator $rows
     * @param array|null $headers - Custom headers (if null, headers will be parsed from the first line)
     * @return \Iterator|AsyncIterator
     * @throws ProcessingException
     * @todo possibility to proceed after exception throwed
     */
    function mapped($rows, array $headers = null)
    {
        if ($rows instanceof \Iterator) {
            return mappedSync($rows, $headers);
        }
        return mappedAsync($rows, $headers);
    }

    /**
     * @param \Iterator $rows
     * @param array|null $headers
     * @return \Iterator|array[]
     * @throws ProcessingException
     */
    function mappedSync(\Iterator $rows, array $headers = null): \Iterator
    {
        if (!$rows->valid()) {
            return $rows;
        }
        if (!$headers) {
            $headers = $rows->current();
            $rows->next();
        }
        $row = null;
        while ($rows->valid()) {
            try {
                $row = array_combine($headers, $rows->current());
            } catch (\ErrorException $e) {
                // just pass
            }
            $rows->next();
            if (($row === false) || is_null($row)) {
                throw new ProcessingException(sprintf(
                    "Error mapping row: column count mismatch in row %d",
                    $rows->key() + 1
                ));
            }
            yield $row;
        }
    }

    /**
     * @param AsyncIterator $rows
     * @param array|null $headers
     * @return AsyncIterator
     */
    function mappedAsync(AsyncIterator $rows, array $headers = null): AsyncIterator
    {
        return new Producer(function (callable $emit) use ($rows, $headers) {
            if (!$headers && yield $rows->advance()) {
                $headers = $rows->getCurrent();
            }
            $row = null;
            $num = 0;
            while (yield $rows->advance()) {
                $num++;
                try {
                    $row = array_combine($headers, $rows->getCurrent());
                } catch (\ErrorException $e) {
                    // just pass
                }
                if (($row === false) || is_null($row)) {
                    throw new ProcessingException(sprintf(
                        "Error mapping row: column count mismatch in row %d",
                        $num
                    ));
                }
                yield $emit($row);
            }
        });
    }

    /**
     * @param \Iterator|AsyncIterator $rows
     * @return \Iterator|AsyncIterator
     */
    function unescaped($rows)
    {
        if ($rows instanceof \Iterator) {
            return unescapedSync($rows);
        }
        return unescapedAsync($rows);
    }

    /**
     * @param \Iterator $rows
     * @return \Iterator
     */
    function unescapedSync(\Iterator $rows): \Iterator
    {
        foreach ($rows as $row) {
            yield array_map('stripcslashes', $row);
        }
    }

    /**
     * @param AsyncIterator $rows
     * @return AsyncIterator
     */
    function unescapedAsync(AsyncIterator $rows): AsyncIterator
    {
        return new Producer(function (callable $emit) use ($rows) {
            while (yield $rows->advance()) {
                yield $emit(array_map('stripcslashes', $rows->getCurrent()));
            }
        });
    }

    /**
     * @param string $filename
     * @param Options|null $options
     * @return \Iterator
     * @throws IOException
     * @throws \CSV\Exception
     * @throws \CSV\ParseException
     */
    function parseFile(string $filename, Options $options = null): \Iterator
    {
        $fp = fopen($filename, 'r');
        if (!$fp) {
            throw new IOException("Can't open {$filename} for reading");
        }
        try {
            foreach ((new Parser($options))->parse($fp) as $row) {
                yield $row;
            }
        } finally {
            fclose($fp);
        }
    }

}

namespace CSV\Helpers\IO {

    use Amp\ByteStream\InMemoryStream;
    use Amp\ByteStream\InputStream;
    use Amp\ByteStream\ResourceInputStream;
    use Amp\Iterator as AsyncIterator;
    use Amp\Producer;
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
     * @param resource|string|DataReaderInterface $src
     * @param int $maxMemUsage
     * @return array [resource $stream, bool $isTemporary]
     * @throws \InvalidArgumentException
     */
    function toStream($src, int $maxMemUsage = 10 * 1024 * 1024): array
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

    /**
     * @param resource|string $src
     * @return InputStream
     */
    function toAsyncStream($src): InputStream
    {
        switch (true) {
            case $src instanceof InputStream:
                return $src;
            case is_resource($src):
                return new ResourceInputStream($src);
            case is_string($src):
                return new InMemoryStream($src);
            default:
                throw new \InvalidArgumentException(sprintf(
                    "Can't convert argument of type %s to InputStream",
                    gettype($src)
                ));
        }
    }

    /**
     * @param InputStream $input
     * @return AsyncIterator
     */
    function readlineAsync(InputStream $input): AsyncIterator
    {
        return new Producer(function (callable $emit) use ($input) {
            $buffer = '';
            while (($chunk = yield $input->read()) !== null) {
                while (($pos = strpos($chunk, "\n")) !== false) {
                    $line = $buffer . substr($chunk, 0, $pos);
                    yield $emit($line);
                    $buffer = '';
                    $chunk = substr($chunk, $pos + 1);
                }
                $buffer.= $chunk;
            }
            if ($buffer !== '') {
                yield $emit($buffer);
            }
        });
    }

    /**
     * @param int $maxMemUsage
     * @return bool|resource
     */
    function makeTmpStream(int $maxMemUsage = 10 * 1024 * 1024)
    {
        return fopen("php://temp/maxmemory:{$maxMemUsage}", 'r+');
    }

}


