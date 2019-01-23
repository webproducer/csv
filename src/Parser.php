<?php
namespace CSV;

/**
 * Class Parser
 *
 * Facade for one of concrete parser implementations
 *
 * @package CSV
 */
class Parser extends BaseParser
{

    /**
     * @inheritdoc
     */
    public function parse($stream): \Iterator
    {
        $realParser = DefaultFactory::get()->make($this->options);
        return $realParser->parse($stream);
    }

}
