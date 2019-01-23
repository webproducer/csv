<?php
namespace CSV;


class Writer implements WriterInterface
{

    private $writer;

    /**
     * Writer constructor.
     * @param Options|null $options
     * @throws Exception
     */
    public function __construct(Options $options = null)
    {
        $options = $options ?: Options::withDefaults();
        //TODO: make default factory?
        switch ($options->mode) {
            case Options::MODE_RFC4180:
                $this->writer = new RfcWriter($options);
                break;
            case Options::MODE_TSV:
                $this->writer = new TsvWriter($options);
                break;
            default:
                throw new Exception("Unknown write mode: '{$options->mode}'");
        }
    }

    /**
     * @inheritDoc
     */
    public function write(\Iterator $rows, $stream = null): int
    {
        return $this->writer->write($rows, $stream);
    }

    /**
     * @inheritDoc
     */
    public function getContents(): string
    {
        return $this->writer->getContents();
    }


}
