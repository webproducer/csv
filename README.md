# csv
CSV reading, parsing &amp; writing utils

## Features

* Operating according to [RFC 4180](https://tools.ietf.org/html/rfc4180)
* Parsing CSV data from streams - as well as from strings
* All parsed results are accessible through memory efficient generators
* Headers mapping
* UTF-8 support

## Examples

### Parse file (or standart input):

```php
use CSV\Parser;

$src = fopen($argv[1] ?? STDIN, 'r');
foreach ((new Parser)->parse($src) as $row) {
    print_r($row);
}
fclose($src);
```

### Parse from string:

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

$parser = new Parser(new Options("\t"));
```

### Strict mode

```php
use CSV\{Parser, Options};

$parser = new Parser(Options::strict());
```

In the strict mode source data must follow these rules:

* No empty lines
* Same columns count in each row
* Only `CRLF` ("\r\n") must be used as rows divider 

### Generating CSV

```php
use CSV\{Writer, Options};

$writer = new Writer(
    fopen(STDOUT, 'a'),
    new Options(";", Options::ENCODING_ISO)
);
$writer->writeRow(['Name', 'Age', 'City']);
$writer->writeRow(['John "Robby" Robinson', '33', 'Aberdeen']);
$writer->writeRow(['Jane Bridge', '18', 'Springfield']);

```
