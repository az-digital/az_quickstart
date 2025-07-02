# File metadata EXIF

A Drupal module providing a file metadata plugin for the EXIF protocol.


## Features:

Uses the [PHP Exif Library](https://github.com/lsolesen/pel) to read/write EXIF
information to image files, so bypassing the limitations of the standard PHP
Exif extensions which only provides read capabilities.


## Usage examples:

1. __Get EXIF information from a file:__

  a. Prepare collecting metadata for the file located at a desired URI:

```php
  $fmdm = \Drupal::service(FileMetadataManagerInterface::class);
  $my_file_metadata = $fmdm->uri('public::/my_directory/test-exif.jpeg');
```

  b. Get the metadata for the metadata _$key_ required. The value returned is an
     associative array with two keys:
     - 'text': the string representation of the EXIF tag, suitable for
       presentation;
     - 'value': the EXIF tag value in PEL internal format.

```php
 ...
 $val = $my_file_metadata->getMetadata('exif', $key);
 ...
```

  c. EXIF metadata is organized in 'headers' (IFDs) and 'tags'. For this reason,
     the metadata _$key_ can be specified in the ```getMetadata``` method:
     - as a string: in this case, it is assumed that a TAG is specified, and the
       default IFD for that TAG will be used to fetch the information:
     - as an array: in this case, the first and the second array elements
       specify respectively the IFD and the TAG requested. IFD and TAG can be
       strings, or integers.
     The following statements all are equivalent in returning the same
     information about the 'ApertureValue' TAG in the 'Exif' IFD:

```php
  ...
  $aperture = $my_file_metadata->getMetadata('exif', 'ApertureValue');
  $aperture = $my_file_metadata->getMetadata('exif', ['Exif', 'ApertureValue']);
  $aperture = $my_file_metadata->getMetadata('exif', [2, 'ApertureValue']);
  $aperture = $my_file_metadata->getMetadata('exif', ['Exif', 0x9202]);
  $aperture = $my_file_metadata->getMetadata('exif', [2, 0x9202]);
  ...
```

  d. Get a list of IFDs:

```php
  ...
  $my_file_metadata->getSupportedKeys('exif', ['ifds' => TRUE]);
  ...
```

  e. Get a list of TAGs for a given IFD:

```php
  ...
  $my_file_metadata->getSupportedKeys('exif', ['ifd' => 'GPS']);
  ...
```

  f. Walk through all possible IFDs/TAGs and build a table with results:

```php
  ...
  $header = [
    ['data' => 'key'],
    ['data' => 'text'],
    ['data' => 'value'],
  ];
  $rows = [];
  foreach ($my_file_metadata->getSupportedKeys('exif', ['ifds' => TRUE]) as $ifd) {
    $rows[] = [['data' => $ifd[0], 'colspan' => 3]];
    $keys = $my_file_metadata->getSupportedKeys('exif', ['ifd' => $ifd[0]]);
    foreach ($keys as $key) {
      $x = $my_file_metadata->getMetadata('exif', $key);
      if ($x) {
        $rows[] = ['data' => [$key[1], $x ? $x['text'] : NULL, $x ? var_export($x['value'], TRUE) : NULL]];
      }
    }
  }
  return [
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  ];
```

2. __Change EXIF information and save to file:__

Changing EXIF information and saving it back to the file requires some
understanding of how the PEL library manages EXIF entries.

a. If you are changing information that is _already_ existing in the source
   file, then you can use the plugin ```setMetadata``` method, passing the value
   that the PEL Exif entry expects:

```php
  ...
  $my_file_metadata->setMetadata('exif', 'Orientation', 7);
  ...
```

b. If you are _adding_ a TAG that was not existing before, you need to pass a
   new PEL Exif entry, as expected for that entry. This can also be done as an
   alternative to change an existing entry:

```php
  ...
  $artist_tag = \Drupal::service(ExifTagMapperInterface::class)->resolveKeyToIfdAndTag('Artist');
  $value = 'MEEeeee!';
  $artist = new PelEntryAscii($artist_tag['tag'], $value);
  $my_file_metadata->setMetadata('exif', 'Artist', $artist);
  ...
```

c. Save changed metadata to file:

```php
  ...
  $my_file_metadata->saveMetadataToFile('exif');
  ...
```
