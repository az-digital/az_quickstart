# MimeMap

[![Tests](https://github.com/FileEye/MimeMap/actions/workflows/php-unit.yml/badge.svg)](https://github.com/FileEye/MimeMap/actions/workflows/php-unit.yml)
[![PHPStan level](https://img.shields.io/badge/PHPStan%20level-max-brightgreen.svg?style=flat)](https://github.com/FileEye/MimeMap/actions/workflows/code-quality.yml)
[![codecov](https://codecov.io/gh/FileEye/MimeMap/branch/master/graph/badge.svg?token=SUAMNKZLEW)](https://codecov.io/gh/FileEye/MimeMap)
[![Latest Stable Version](https://poser.pugx.org/fileeye/mimemap/v/stable)](https://packagist.org/packages/fileeye/mimemap)
[![Total Downloads](https://poser.pugx.org/fileeye/mimemap/downloads)](https://packagist.org/packages/fileeye/mimemap)
[![License](https://poser.pugx.org/fileeye/mimemap/license)](https://packagist.org/packages/fileeye/mimemap)

A PHP library to handle MIME Content-Type fields and their related file
extensions.


## Features

- Parses MIME Content-Type fields
- Supports the [RFC 2045](https://www.ietf.org/rfc/rfc2045.txt) specification
- Provides utility functions for working with and determining info about MIME
  types
- Map file extensions to MIME types and vice-versa
- Automatically update the mapping between MIME types and file extensions from
  the most authoritative sources available, [Apache's documentation](http://svn.apache.org/viewvc/httpd/httpd/trunk/docs/conf/mime.types?view=log)
  and the [freedesktop.org project](http://freedesktop.org).
- PHPUnit tested, 100% test coverage
- PHPStan tested, level 10


## Credits

MimeMap is a fork of PEAR's [MIME_Type](https://github.com/pear/MIME_Type) package.
See all the [original contributors](https://github.com/pear/MIME_Type/graphs/contributors).

Note that in comparison with PEAR's MIME_Type, this library has a different
scope, mainly focused on finding the mapping between each MIME type and its
generally accepted file extensions.
Features to detect the MIME type of a file have been removed. The [symfony/http-foundation](https://github.com/symfony/http-foundation)
library and its [MimeTypeGuesser](https://api.symfony.com/master/Symfony/Component/HttpFoundation/File/MimeType/MimeTypeGuesser.html)
API are the suggested components to cover that use case.


### Alternative packages

MimeMap's main difference from similar packages is that it provides
functionalities to use multiple type-to-extension maps and to change the
mapping either at runtime or statically in PHP classes.
See [wgenial/php-mimetyper](https://github.com/wgenial/php-mimetyper#other-php-libraries-for-mime-types)
for a nice list of alternative PHP libraries for MIME type handling.


## Installation

```
$ composer require fileeye/mimemap
```


## Usage

See latest documentation [here](https://fileeye.github.io/MimeMap/), automated with phpDocumentor.

### Basic

The package comes with a default map that describes MIME types and the file
extensions normally associated to each MIME type.
The map also stores information about MIME type _aliases_, (alternative
_media/subtype_ combinations that describe the same MIME type), and the
descriptions of most MIME types and of the acronyms used.

For example: the MIME type _'application/pdf'_
* is described as _'PDF document'_
* the PDF acronym is described as _'PDF: Portable Document Format'_
* is normally using a file extension _'pdf'_
* has aliases such as _'application/x-pdf'_, _'image/pdf'_

The API the package implements is pretty straightforward:


1. You have a MIME type, and want to get the file extensions normally associated
to it:

  ```php
  use FileEye\MimeMap\Type;
  ...
  $type = new Type('image/jpeg');

  print_r($type->getExtensions());
  // will print ['jpeg', 'jpg', 'jpe']

  print_r($type->getDefaultExtension());
  // will print 'jpeg'

  // When passing an alias to a MIME type, the API will
  // return the extensions to the parent type:
  $type = new Type('image/pdf');

  print_r($type->getDefaultExtension());
  // will print 'pdf' which is the default extension for 'application/pdf'
  ```

2. Viceversa, you have a file extensions, and want to get the MIME type normally
associated to it:

  ```php
  use FileEye\MimeMap\Extension;
  ...
  $ext = new Extension('xar');

  print_r($ext->getTypes());
  // will return ['application/vnd.xara', 'application/x-xar']

  print_r($ext->getDefaultType());
  // will return 'application/vnd.xara'
  ```

3. You have a raw MIME Content-Type string and want to add a parameter:

  ```php
  use FileEye\MimeMap\Type;
  ...
  $type = new Type('text / (Unstructured text)  plain  ; charset = (UTF8, not ASCII) utf-8');
  $type->addParameter('lang', 'it', 'Italian');

  echo $type->toString(Type::SHORT_TEXT);
  // will print 'text/plain'

  echo $type->toString(Type::FULL_TEXT);
  // will print 'text/plain; charset="utf-8"; lang="it"'

  echo $type->toString(Type::FULL_TEXT_WITH_COMMENTS);
  // will print 'text/plain (Unstructured text); charset="utf-8" (UTF8, not ASCII), lang="it" (Italian)'
  ```

4. You have a MIME Content-Type string and want to add the type's description as a comment:

  ```php
  use FileEye\MimeMap\Type;
  ...
  $type = new Type('text/html');

  $type_desc = $type->getDescription();
  $type->setSubTypeComment($type_desc);
  echo $type->toString(Type::FULL_TEXT_WITH_COMMENTS);
  // will print 'text/html (HTML document)'

  // Setting the $include_acronym parameter of getDescription to true
  // will extend the description to include the meaning of the acronym
  $type_desc = $type->getDescription(true);
  $type->setSubTypeComment($type_desc);
  echo $type->toString(Type::FULL_TEXT_WITH_COMMENTS);
  // will print 'text/html (HTML document, HTML: HyperText Markup Language)'
  ```


### Specify alternative MIME type mapping


You can also alter the default map at runtime, either by adding/removing
mappings, or indicating to MimeMap to use a totally different map. The
alternative map must be stored in a PHP class that extends from
`\FileEye\MimeMap\Map\AbstractMap`.

1. You want to add an additional MIME type to extension mapping to the
default class:

  ```php
  use FileEye\MimeMap\Extension;
  use FileEye\MimeMap\MapHandler;
  use FileEye\MimeMap\Type;
  ...
  $map = MapHandler::map();
  $map->addTypeExtensionMapping('foo/bar', 'baz');

  $type = new Type('foo/bar');
  $default_extension = $type->getDefaultExtension();
  // will return 'baz'

  $ext = new Extension('baz');
  $default_type = $ext->getDefaultExtension();
  // will return 'foo/bar'
  ```

2. You want to set an alternative map class as default:

  ```php
  use FileEye\MimeMap\Extension;
  use FileEye\MimeMap\MapHandler;
  use FileEye\MimeMap\Type;
  ...
  MapHandler::setDefaultMapClass('MyProject\MyMap');
  ...
  ```

3. You can also use the alternative map just for a single Type or Extension
object:

  ```php
  use FileEye\MimeMap\Extension;
  use FileEye\MimeMap\Type;
  ...
  $type = new Type('foo/bar', 'MyProject\MyMap');
  $ext = new Extension('baz', 'MyProject\MyMap');
  ```


## Development


### Updating the extension mapping code

The default extension-to-type mapping class can be updated from the sources'
code repositories, using the `fileeye-mimemap` utility:

```
$ cd [project_directory]/vendor/bin
$ fileeye-mimemap update
```

By default, the utility fetches a mapping source available from the [Apache's documentation](http://svn.apache.org/viewvc/httpd/httpd/trunk/docs/conf/mime.types?view=co)
website, merges it with another mapping source from the [freedesktop.org project](https://gitlab.freedesktop.org/xdg/shared-mime-info/-/blob/master/data/freedesktop.org.xml.in),
then integrates the result with any overrides specified in the
`resources/default_map_build.yml` file, and finally updates the PHP file where
the `\FileEye\MimeMap\Map\DefaultMap` class is stored.

The `--script` and `--class` options allow specifying a different update logic
and a different class file to update. Type
```
$ fileeye-mimemap update --help
```
to get more information.
