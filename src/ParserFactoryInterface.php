<?php
namespace CSV;


interface ParserFactoryInterface
{

    public function make(Options $options = null): ParserInterface;

}
