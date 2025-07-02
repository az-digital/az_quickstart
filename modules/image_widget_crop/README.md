[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/woprrr/image_widget_crop/badges/quality-score.png?b=8.x-2.x)](https://scrutinizer-ci.com/g/woprrr/image_widget_crop/?branch=8.x-2.x)

# ImageWidgetCrop module
Provides an interface for using the features of the [Crop API]. This element
provides an UX for using a crop on all fields images or file elements. This
module has particularity to purpose capability to crop the same image by “Crop
type” configured. It’s very useful for editorial sites or media management
sites.

## Table of contents
- [Requirements](#requirements)
- [Roadmap](#roadmap)
- [Known problems](#known-problems)
- [Try out the module](#try-out-the-module)
- [Installation](#installation)
- [Configuration](#configuration)
- [Options](#options)
- [Recommended modules](#recommended-modules)

## Requirements
* Drupal Module [Crop API].
* Library [Cropper].

## Roadmap
You can follow the evolution of this module [here]
(https://www.drupal.org/node/2832789).

## Known problems
* Release 1.x
1. Not compatible with 'Boostrap theme', please switch on 2.x branch to have
   full support with all themes.
2. Support multiple crop variants per URI and crop type, more information
   [here](https://www.drupal.org/node/2617818).

* Release 2.x
1. Support multiple crop variants per URI and crop type, more information
   [here](https://www.drupal.org/node/2617818).

## Try out the module
You can Test ImageWidgetCrop in action directly with the sub-module
"ImageWidgetCrop example" to test different use cases of this module.
You have two choices to use example module :

### Local files
1. Download and extract project.
2. Enable it (`drush en image_widget_crop_examples -y`) or (`admin/modules`)
   and enable modules.
3. Your site frontpage have changed to ImageWidgetCrop Examples welcome page
   and list all possible examples to try the modules.

### simplytest.me (Online)
If you prefer to use SimplyTest.me service, you can test the module online in
two versions.

1. Use one of links [1.x (Stable)] Or [2.x (Stable)].
2. Click on the button "Lauch sandbox" and follow common way to install
   Drupal 8.
3. Enable ImageWidgetCrop example module on Extend page (`admin/modules`) and
   enable module.
4. Enable it (`drush en image_widget_crop_examples -y`) or (`admin/modules`)
   and enable id.
5. Your site frontpage have changed to ImageWidgetCrop Examples welcome page
   and list all possible examples to try the modules.

[1.x (Stable)]: https://simplytest.me/project/image_widget_crop/8.x-1.5?add[]
=media_entity&add[]=media_entity_image&add[]=file_entity&add[]=entity&add[]
=token&add[]=inline_entity_form

[2.x (Stable)]: https://simplytest.me/project/image_widget_crop/8.x-2.x?add[]
=media_entity&add[]=media_entity_image&add[]=file_entity&add[]=entity&add[]
=token&add[]=inline_entity_form&add[]=imce&add[]=entity_browser&add[]=ctools

## Installation
1. Download and extract the module to your (`sites/all/modules/contrib`) folder.
2. Enable the module on the Drupal Modules page (`admin/modules`) or using
   $ drush en

The module is currently using Cropper as a library to display, the cropping
widget.
To properly configure it, do the following:

* Local library:
1. Download the latest version of [Cropper].
2. Copy the dist folder into:
- /libraries/cropper/dist
3. Enable the libraries module.

* External library:
1. Set the external URL for the minified version of the library and CSS
   file, in Image Crop Widget settings (`/admin/config/media/crop-widget`),
   found at https://cdnjs.com/libraries/cropper.

NOTE: The external library is set by default when you enable the module.

## Configuration

ImageWidgetCrop can be used in different contexts.

### FieldWidget:

* Create a Crop Type (`admin/config/media/crop`)
* Create ImageStyles  
* add Manual crop effect, using your Crop Type, (to apply your crop selection).
* Create an Image field.
* In its form display, at (`admin/structure/types/manage/page/form-display`):
* set the widget for your field to ImageWidgetCrop 
* at select your crop types in the Crop settings list. You can configure the
  widget to create different crops on each crop types. For example, if
  you have an editorial site, you need to display an image on different
  places. With this option, you can set an optimal crop zone for each of
  the image styles applied to the image.
* Set the display formatter Image and choose your image style, or responsive
  image styles.
* Go add an image with your widget and crop your picture, by crop types used
  for this image.

### FileEntity:

* The (`image_crop`) element are already implemented to use,  an general
  configuration of module.
* In its ImageWidgetCrop general configuration, at 
  (`admin/config/media/crop-widget`):
* open (`GENERAL CONFIGURATION`) fieldset.
* at select your crop types in the Crop settings list. You can configure the
  element to create different crops on each crop types. For example, if
  you have an editorial site, you need to display an image on different
  places. With this option, you can set an optimal crop zone for each of
  the image styles applied to the image
* Verify your content using (`image`) field type configured, with
  (`Editable file`) form widget.
* Add an File into content (`node/add/{your-content-type}`) upload your file,
  click to (`Edit`) button and crop your picture,   by crop types used for
  this image.

### Form API:

* Implement (`image_crop`) form element.
* Set all variables elements Or use general configuration of module.

#### Example of Form API implementation:

##### Common element configuration of ImageWidgetCrop:
```php
$crop_config = \Drupal::config('image_widget_crop.settings');
$form['image_crop'] = [
  '#type' => 'image_crop',
  '#file' => $file_object,
  '#crop_type_list' => $crop_config->get('settings.crop_list'),
  '#crop_preview_image_style' => $crop_config->get('settings.crop_preview_
   image_style'),
  '#show_default_crop' => $crop_config->get('settings.show_default_crop'),
  '#show_crop_area' => $crop_config->get('settings.show_crop_area'),
  '#warn_mupltiple_usages' => $crop_config->get('settings.warn_
   mupltiple_usages'),
];
```
##### Custom element configuration
```php
$form['image_crop'] = [
  '#type' => 'image_crop',
  '#file' => $file_object,
  '#crop_type_list' => ['crop_16_9', 'crop_free'],
  '#crop_preview_image_style' => 'crop_thumbnail',
  '#show_default_crop' => FALSE,
  '#show_crop_area' => FALSE,
  '#warn_mupltiple_usages' => FALSE,
];
```

## Options
You may use ImageWidgetCrop configuration in few contexts and define different
options to have desired features by context.
If you want to change the global default options you can change it at
(`/admin/config/media/crop-widget`) and use configuration
`\Drupal::config('image_widget_crop.settings')`.

### crop_type_list

- Type: `Array`
- Default: `empty`

List of all CropTypes configured onto ImageStyle to use and define crop.

### crop_preview_image_style

- Type: `String`
- Default: `crop_thumbnail`

Control the sizes of image to crop, you do conserve the aspect ratio of image
(Only use Scale in `Width` OR `Height` not both).

### show_default_crop

- Type: `Boolean`
- Default: `true`

Automatically initialize Crop Area on open `crop` element or file upload if
`show_crop_area` option is enable.

### show_crop_area

- Type: `Boolean`
- Default: `true`

Automatically open `image_crop` detail elements.

### warn_mupltiple_usages

- Type: `Boolean`
- Default: `true`

Show a message to prevent users if current crop is used in other places and
risk to affect in multiple places in same time.

## Recommended modules
All of these modules are supported and tested with Image Widget Crop.

* [Crop API]: Provides basic API for images cropping.
* [IMCE]: Now supported by all versions of Image Widget Crop. We just have an
  option to enable of image_crop elements to use it.
* [File (Field) Paths]: We support this module and work with Image Widget Crop.
* [Bootstrap (theme)]: During lot of efforts to made compatibility with
  Boostrap all themes are compatible with Image Widget Crop Thank to
  @markcarver for his precious help.
* [Entity Browser]: Fully supported by this module.
* [File Entity (fieldable files)]: Fully compatible with this module.
* [Automated Crop]: Soon Image Widget Crop use this service to provide a
  powerful feature request (Automatic crop).

[Cropper]: https://github.com/fengyuanchen/cropper
[Crop API]: https://github.com/drupal-media/crop
[IMCE]: https://www.drupal.org/project/imce
[File (Field) Paths]: https://www.drupal.org/project/filefield_paths
[Bootstrap (theme)]: https://www.drupal.org/project/bootstrap
[Entity Browser]: https://www.drupal.org/project/entity_browser
[File Entity (fieldable files)]: https://www.drupal.org/project/file_entity
[Automated Crop]: https://www.drupal.org/project/automated_crop
