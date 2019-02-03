<?php
namespace CSV\Async;

use Amp\{Iterator, Producer};
use CSV\{Options, ParseException};
use function CSV\Helpers\unescapedAsync;
use function CSV\Helpers\IO\{readlineAsync, toAsyncStream};


class TsvParser implements ParserInterface
{
    /** @var Options */
    private $options;

    /**
     * TsvParser constructor.
     * @param Options $options
     */
    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
    public function parse($input): Iterator
    {
        $rows = $this->generate($input);
        return $this->options->autoEscape ? unescapedAsync($rows) : $rows;
    }

    private function generate($input): Iterator
    {
        $input = toAsyncStream($input);
        return new Producer(function (callable $emit) use ($input) {
            $lines = readlineAsync($input);
            $num = 0;
            while (yield $lines->advance()) {
                $num++;
                $line = rtrim($lines->getCurrent(), "\r\n");
                if (empty($line)) {
                    if ($this->options->strict) {
                        throw new ParseException("Line {$num} is empty (empty lines are not allowed in the strict mode)");
                    }
                    continue;
                }
                yield $emit(explode(
                    $this->options->separator,
                    $line
                ));
            }
        });
    }

}
