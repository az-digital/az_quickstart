# File metadata Font

A Drupal module providing a file metadata plugin to retrieve information from
font files.


## Features:

Uses the [PHP Font Lib](https://github.com/dompdf/php-font-lib) to read font
information from TTF/OTF/WOFF font files.


## Usage examples:

1. Use the _FileMetadataManagerInterface::class_ service to prepare collecting metadata for
   the font located at a desired URI:

  ```php
    $fmdm = \Drupal::service(FileMetadataManagerInterface::class);
    $my_font_metadata = $fmdm->uri('public::/my_font_directory/arial.ttf');
    ...
  ```

2. Get the value of a key:

  ```php
    ...
    $font_name = $my_font_metadata->getMetadata('font', 'FontName');
    ...
  ```

3. Get an array with all the metadata values:

  ```php
    ...
    $my_font_info = [];
    foreach ($my_font_metadata->getSupportedKeys('font') as $key) {
      $my_font_info[$key] = $my_font_metadata->getMetadata('font', $key);
    }
    ...
  ```


## Available metadata keys:

Key                 |
--------------------|
FontType            |
FontWeight          |
Copyright           |
FontName            |
FontSubfamily       |
UniqueID            |
FullName            |
Version             |
PostScriptName      |
Trademark           |
Manufacturer        |
Designer            |
Description         |
FontVendorURL       |
FontDesignerURL     |
LicenseDescription  |
LicenseURL          |
PreferredFamily     |
PreferredSubfamily  |
CompatibleFullName  |
SampleText          |
