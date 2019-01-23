<?php
namespace CSV;


abstract class BaseWriter implements WriterInterface
{

    protected $options;
    private $buffer;

    /**
     * BaseWriter constructor.
     * @param Options|null  $options
     */
    public function __construct(Options $options = null)
    {
        $this->options = $options ?: Options::withDefaults();
    }

    /**
     * @inheritDoc
     */
    public function write(iterable $rows, $stream = null): int
    {
        $stream = $stream ?: $this->initBuffer();
        $num = 0;
        foreach ($rows as $row) {
            if (fputs($stream, $this->makeRow($row)) === false) {
                throw new WriteException(sprintf("Can't write to resource #%d", $stream));
            }
            $num++;
        }
        return $num;
    }

    /**
     * @inheritDoc
     */
    public function getContents(): string
    {
        if (!$this->buffer) {
            return '';
        }
        try {
            rewind($this->buffer);
            return stream_get_contents($this->buffer);
        } finally {
            fclose($this->buffer);
            $this->buffer = null;
        }
    }

    abstract public function makeRow(array $values): string;

    private function initBuffer()
    {
        if ($this->buffer) {
            $this->getContents(); // just to flush
        }
        $this->buffer = fopen('php://memory','r+');
        return $this->buffer;
    }


}
