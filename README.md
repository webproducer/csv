# csv
CSV reading, parsing &amp; writing utils

## Features

* Operating according to [RFC 4180](https://tools.ietf.org/html/rfc4180)
* TSV format (as [described here](https://www.iana.org/assignments/media-types/text/tab-separated-values)) is also supported (and it can be parsed much faster than CSV/RFC4180) 
* Parsing CSV data from streams - as well as from strings
* All parsed results are accessible through memory efficient generators
* Headers mapping
* UTF-8 support

## Examples

### Parse stream

```php
use CSV\Parser;

foreach ((new Parser)->parse(STDIN) as $row) {
    print_r($row);
}
// note: stream won't close automatically 
```

### Parse file

```php
use function CSV\Helpers\parseFile;

foreach (parseFile($argv[1]) as $row) {
    print_r($row);
}
```

### Parse string

```php
use CSV\Parser;

$src = 'name,page_slug,parent_slug,icon_name
Gates,gates,,fa-gears
Gates list,gate,gates,fa-list
Gates statistics,gates/statistics,gates,fa-table
Clients,client,,fa-circle-o';

foreach ((new Parser)->parse($src) as $row) {
    print_r($row);
}
```

Will output:

```
Array
(
    [0] => name
    [1] => page_slug
    [2] => parent_slug
    [3] => icon_name
)
Array
(
    [0] => Gates
    [1] => gates
    [2] =>
    [3] => fa-gears
)
...
```

### Headers mapping

```php
use CSV\Parser;
use function CSV\Helpers\mapped;

// $src = /* ... same as in previous example ... */; 

foreach (mapped((new Parser())->parse($src)) as $row) {
    print_r($row);
}
```

Will output:

```
Array
(
    [name] => Gates
    [page_slug] => gates
    [parent_slug] =>
    [icon_name] => fa-gears
)
Array
(
    [name] => Gates list
    [page_slug] => gate
    [parent_slug] => gates
    [icon_name] => fa-list
)
...
```

### Custom fields separator

```php
use CSV\{Parser, Options};

$parser = new Parser(new Options(";"));
```

### Strict mode

```php
use CSV\{Parser, Options};

$parser = new Parser(Options::strict());
```

In the strict mode source data must comply with the following rules:

* No empty lines
* Each row must contain same fields count
* Only `CRLF` ("\r\n") used as rows divider

### TSV parsing

```php
use CSV\{Parser, Options};

$parser = new Parser(Options::tsv());
```

### Writing CSV (RFC 4180)

```php
use CSV\{Writer, Options};

$data = [
    ['Name', 'Age', 'Address'],
    ['John "Robby" Robinson', '33', 'Aberdeen'],
    ['Jane Bridge', '18', "Springfield\n123\tMain street"]
];

// Default (RFC) writer
$writer = new Writer();
$writer->write($data, STDOUT);
```

### Writing TSV

```php
// TSV format
$writer = new Writer(Options::tsv());
$writer->write($data, STDOUT);
```

### Writing from generator (using internal string buffer)

```php
use CSV\{Writer, Options};
$writer = new Writer(Options::tsv());
$generate = function(int $cnt) {
    for ($i=1; $i<=$cnt; $i++) {
        yield [$i, date("D d M", strtotime("{$i} day ago"))];
    }
};
$writer->write($generate(30));
echo $writer->getContents(); // used memory will be auto-flushed now
```
