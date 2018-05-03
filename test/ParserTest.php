<?php

namespace CSV\Test;

use CSV\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * @param string $string
     * @param array $expected
     * @throws \Exception
     * @dataProvider parseStringDataProvider
     */
    public function testParseString($string, array $expected)
    {
        $this->assertSame($expected, Parser::parseString($string));
    }

    public function parseStringDataProvider()
    {
        return [
            ['', []],
            ['1,2,3', ['1', '2', '3']],
            ['1,"2",3', ['1', '2', '3']],
            ['1,"""2""",3', ['1', '"2"', '3']],
            ['1,"\"2\"",3', ['1', '\"2\"', '3']],
            ['1,""","",2",3', ['1', '",",2', '3']],
            ['1,""","",\"2",3', ['1', '",",\"2', '3']],
        ];
    }

    /**
     * @param $string
     * @throws \Exception
     * @dataProvider parseStringDataProviderException
     */
    public function testParseStringException($string)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Illegal unescaped quote.');
        Parser::parseString($string);
    }

    public function parseStringDataProviderException()
    {
        return [
            ['1,""",3'],
            ['1,""2"",3'],
            ['1,""","",2"",3'],
        ];
    }
}
