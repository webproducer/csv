<?php
namespace CSV;


abstract class BaseParser implements ParserInterface
{

    protected $options;

    /**
     * BaseParser constructor.
     * @param Options|null $options
     */
    public function __construct(Options $options = null)
    {
        $this->options = $options ?: Options::withDefaults();
    }


}
