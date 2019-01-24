<?php
namespace CSV;


class DefaultFactory implements ParserFactoryInterface
{

    private static $inst;
    private $ffuncs = [];

    public static function get(): ParserFactoryInterface
    {
        if (!self::$inst) {
            self::$inst = self::makeDefault();
        }
        return self::$inst;
    }

    public function make(Options $options = null): ParserInterface
    {
        $options = $options ?: Options::defaults();
        $ffunc = $this->ffuncs[$options->mode] ?? function(Options $options) {
            throw new Exception("Unknown mode: {$options->mode}");
        };
        return call_user_func($ffunc, $options);
    }

    public function registerFactoryFunc(string $mode, callable $callback)
    {
        $this->ffuncs[$mode] = $callback;
    }

    private static function makeDefault(): ParserFactoryInterface
    {
        $f = new self();
        $f->registerFactoryFunc(Options::MODE_RFC4180, function(Options $options) {
            return $options->strict ? new RfcParser($options) : new BuiltinParser($options);
        });
        $f->registerFactoryFunc(Options::MODE_TSV, function(Options $options) {
            return new TsvParser($options);
        });
        return $f;
    }




}
