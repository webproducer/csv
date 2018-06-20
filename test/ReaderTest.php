<?php

namespace CSV\Test;

use CSV\Reader;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    private static $resource;
    /** @var Reader */
    private $reader;
    private static $lines = [
        ['one', 'one', 'one', 'one', 'one'],
        ['two', 'two', 'two', 'two', 'two'],
    ];

    public static function setUpBeforeClass()
    {
        self::$resource = fopen('php://temp', 'r+');
        foreach (self::$lines as $lineChunks) {
            fputcsv(self::$resource, $lineChunks);
        }
        rewind(self::$resource);
    }

    public function setUp()
    {
        rewind(self::$resource);
        $this->reader = new Reader(self::$resource);
    }

    public function testRewind()
    {
        $firstLineLength = strlen(implode(',', self::$lines[0]) . PHP_EOL);
        $this->assertEquals($firstLineLength, ftell(self::$resource));
        fseek(self::$resource, 15);
        $this->assertEquals(15, ftell(self::$resource));
        $this->reader->rewind();
        $this->assertEquals($firstLineLength, ftell(self::$resource));
    }

    public function testValid()
    {
        $this->assertTrue($this->reader->valid());
        $this->assertFalse((new Reader(fopen('php://temp', 'r')))->valid());
    }

    /**
     * @throws \Exception
     */
    public function testCurrent()
    {
        $this->assertSame($this->reader->current(), self::$lines[0]);
        $this->assertSame($this->reader->current(), self::$lines[0]);
    }

    public function testNext()
    {
        $firstLineLength = strlen(implode(',', self::$lines[0]) . PHP_EOL);
        $this->assertEquals($firstLineLength, ftell(self::$resource));
        $secondLineLength = strlen(implode(',', self::$lines[1]) . PHP_EOL);
        $this->reader->next();
        $this->assertEquals($firstLineLength + $secondLineLength, ftell(self::$resource));
    }

    /**
     * @depends testRewind
     * @depends testValid
     * @depends testCurrent
     * @depends testNext
     */
    public function testIterator()
    {
        $lines = [];
        foreach ($this->reader as $line) {
            $lines[] = $line;
        }
        $this->assertSame(self::$lines, $lines);
    }
}
