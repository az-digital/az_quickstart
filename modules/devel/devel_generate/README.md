[[_TOC_]]

This module may be used to create entities that contain sample content. This is
useful when showing off your site to a client, for example. Even if the content
is not yet available, the site can show its look and feel and behavior.

The sample entities may be created via the Web or via the included Drush commands
like `drush genc`.

#### Recommended Modules

- [Devel Images Provider](http://drupal.org/project/devel_image_provider) allows to configure external providers for images.

#### Custom plugins

This module creates the _DevelGenerate_ plugin type.

All you need to do to provide a new instance for DevelGenerate plugin type
is to create your class extending `DevelGenerateBase` and following these steps:

1. Declare your plugin with annotations:
    ````
    /**
     * Provides a ExampleDevelGenerate plugin.
     *
     * @DevelGenerate(
     *   id = "example",
     *   label = @Translation("example"),
     *   description = @Translation("Generate a given number of example elements."),
     *   url = "example",
     *   permission = "administer example",
     *   settings = {
     *     "num" = 50,
     *     "kill" = FALSE,
     *     "another_property" = "default_value"
     *   }
     * )
     */
    ````
1. Implement the `settingsForm` method to create a form using the properties
from the annotations.
1. Implement the `handleDrushParams` method. It should return an array of
values.
1. Implement the `generateElements` method. You can write here your business
logic using the array of values.

#### Notes

- You can alter existing properties for every plugin by implementing
`hook_devel_generate_info_alter`.
- DevelGenerateBaseInterface details base wrapping methods that most
DevelGenerate implementations will want to directly inherit from
`Drupal\devel_generate\DevelGenerateBase`.
- To give support for a new field type the field type base class should properly
implement `\Drupal\Core\Field\FieldItemInterface::generateSampleValue()`.
Devel Generate automatically uses the values returned by this method during the
generate process for generating placeholder field values. For more information
see: https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Field!FieldItemInterface.php/function/FieldItemInterface::generateSampleValue
- For Drupal 10, the webprofiler module has broken out to its own project at https://www.drupal.org/project/webprofiler
