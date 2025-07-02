# File metadata manager

A Drupal module providing a file metadata manager service and API. Allows to
get, via an unified API, information stored in files like EXIF photo
information, TrueType font information, etc.

Metadata protocols are pluggable. Developers can implement a plugin and use the
service framework to get the metadata required.

The following plugins are provided by the module:

Plugin        | Read | Write | Description                                                  |
--------------|:----:|:-----:|--------------------------------------------------------------|
exif          | X    | X     | Uses the [PHP Exif Library](https://github.com/FileEye/pel) to read/write EXIF information to image files, bypassing the limitations of the standard PHP Exif extensions which only provides read capabilities. Enable the _file_mdm_exif_ submodule to enable this plugin.        |
font          | X    |       | Uses the [PHP Font Lib](https://github.com/dompdf/php-font-lib) to read font information from TTF/OTF/WOFF font files. Enable the _file_mdm_exif_ submodule to enable this plugin.         |
getimagesize  | X    |       | Caches calls to the PHP ```getimagesize()``` function.        |

The module is inspired by discussions at [#2630242 Provide methods to retrieve EXIF image information via the Image object](https://www.drupal.org/node/2630242).


## Features

1. Load from, and save to, file embedded metadata directly from the files.
2. Metadata for a file is statically cached during a request's lifetime. This
   avoids different modules all repeat I/O on the same file.
3. Metadata can be cached in a Drupal cache bin to avoid repeating I/O on the
   files in successive requests.
4. Metadata standards (EXIF, TTF, etc.) are implemented as plugins. The service
   loads the metadata plugin needed based on the calling code request.
5. Manages copying to/from local temporary storage files stored in remote file
   systems, to allow PHP functions that do not support remote stream wrappers
   access the file locally.


## Installing

The module requires [using Composer to manage Drupal site dependencies](https://www.drupal.org/node/2718229).
Once you have setup building your code base using composer, require the module
via

```
  $ composer require drupal/file_mdm:^2
```

then enable the module as usual. Also enable the EXIF or font submodules if
needed.


## Configuration

- Go to _Manage > Configuration > System > File Metadata Manager_ and specify
  the cache retention requirements, in general and/or per each metadata plugin.


## Usage examples

Metadata are retrieved by specifying the protocol plugin required, and the
specific _metadata key_ needed.

For the 'getimagesize' protocol, the metadata keys supported correspond to the
array keys returned by the PHP ```getimagesize()``` function:

Key      | Description                                                  |
---------|--------------------------------------------------------------|
0        | Width of the image                                           |
1        | Height of the image                                          |
2        | The _IMAGETYPE_*_ constant indicating the type of the image  |
3        | Text string with the correct _height="yyy" width="xxx"_ string that can be used directly in an IMG tag |
mime     | The MIME type of the image                                   |
channels | 3 for RGB pictures and 4 for CMYK pictures                   |
bits     | The number of bits for each color                            |

1. __Basic usage:__

  a. Use the _FileMetadataManagerInterface::class_ service to prepare collecting metadata for
     the file located at a desired URI:

```php
   $fmdm = \Drupal::service(FileMetadataManagerInterface::class);
   $my_file_metadata = $fmdm->uri('public::/my_directory/test-image.jpeg');
```

  b. Get the metadata for the specific protocol, identified by the _plugin_, and
     the metadata _key_ required:

```php
   $mime = $my_file_metadata->getMetadata('getimagesize', 'mime');
```

  c. Summarizing, in the context of a controller returning a render array:

```php
  $fmdm = \Drupal::service(FileMetadataManagerInterface::class);
  $my_file_metadata = $fmdm->uri('public::/my_directory/test-image.jpeg');
  $mime = $my_file_metadata->getMetadata('getimagesize', 'mime');
  return ['#markup' => 'MIME type: ' . $mime];
```

  will return something like

```
MIME type: image/jpeg
```

2. __Use a known local temp copy of the remote file to avoid remote file access:__

```php
$fmdm = \Drupal::service(FileMetadataManagerInterface::class);
$my_file_metadata = $fmdm->uri('remote_wrapper::/my_directory/test-image.jpeg');
$my_file_metadata->setLocalTempPath($temp_path);
$mime = $my_file_metadata->getMetadata('getimagesize', 'mime');
```

3. __Make a local temp copy of the remote file to avoid remote file access:__

```php
$fmdm = \Drupal::service(FileMetadataManagerInterface::class);
$my_file_metadata = $fmdm->uri('remote_wrapper::/my_directory/test-image.jpeg');
$my_file_metadata->copyUriToTemp();
$mime = $my_file_metadata->getMetadata('getimagesize', 'mime');
```
