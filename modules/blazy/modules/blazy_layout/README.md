
# ABOUT BLAZY LAYOUT

Provides a single layout with dynamic regions for Layout Builder.

## INSTALLATION
Install the module as usual, more info can be found on:

[Installing Drupal Modules](https://drupal.org/node/1897420)


## USAGE / CONFIGURATION
* Visit Layout builder (LB) pages (`/node/123/layout`), and add a Blazy Layout.
* Three ways to add Media background (image, local video, remote video):
  + **With active entity/Content type:**
    * Add a _multi-value_ Media/ Image field in the active entity/Content type.
    * Upload some images/media (matching the amount of regions which should
      have backgrounds) into the field. If the region total is 10, and you need
      3 backgrounds, just upload 3 items, not 10.
    * At LB: **Add block > Choose a block > Content fields**.
    * Choose **Blazy formatter**, and enable **Use CSS background** option.
    * Use **By delta** option starting from 0 to map field items to any regions
      rather than creating multiple fields for multiple regions.
    * Repeat for any region which may require backgrounds. Adjust **By delta**,
      no need to match one to one delta from field items to regions.
    * FYI, this offers more options, but might be overwhelmed for background
      purposes.
  + **With Block content type**:
    * [/admin/structure/block-content](/admin/structure/block-content), add
      a dedicated background type, says **Background**.
    * [/admin/structure/block-content/manage/background/fields](/admin/structure/block-content/manage/background/fields),
      add a Media field says **Media**, choose Image, Video and Remote video. Multi-value is better for carousel re-use.
    * [/admin/structure/block-content/manage/background/display](/admin/structure/block-content/manage/background/display), choose Blazy formatter, and enable
      **Use CSS background**.
    * Create as many as blocks for background: [/block/add/background](/block/add/background), or on the fly using LB **Create content block**.
    * At LB, either way:
      * **Add block > Create content block**.
      * **Add block > Choose a block > Content block**
  + **With builtin Media library:**
    * Install [Media library form element](https://www.drupal.org/project/media_library_form_element).
      This is alternative to core **Layout Builder Expose All Field Blocks**
      which was deprecated, also a more efficient solution than the first two
      options above to avoid creating useless/ unused Media fields. This
      background is available for all regions, including the main layout. If
      provided, be sure to **NOT** enable **Use CSS background** option for
      other Blazy formatters if provided within the same region to avoid
      multiple and conflicting backgrounds.
    * Select image/media at Layout builder page under:

      **Blazy layout > [Global|Region] > Settings > Styles > Media**
    * **Benefits**: No fields or blocks are created, just re-use, or create,
      media. This is the most efficient by far for simple backgrounds.


### The following is applicable to background options above:
* To have a custom hi-res image/poster for (local|remote) video:
  + Visit bundles:
    * [Remote video](/admin/structure/media/manage/remote_video/fields)
    * [Video](/admin/structure/media/manage/video/fields)
  + Re-use the existing `field_media_image` into each bundle.

    The same principle is applicable to non-background (Document, Audio, etc.)
    when being used with/without background purposes. Normally you would select
    this field under **Blazy formatter > Main stage** to be sure.
  + Select `Media switcher > Image to iframe` option.
* To have unique linkable media:
  + Add a Link or Text field to the Media bundles (not Content type or Node).
  + Select it under **Link** option.
  + Choose **Media switcher > Image linked by Link field**.


## KNOWN ISSUES/ LIMITATIONS
* This module does not provide a CSS framework integration aka framework
  agnostic. Instead using the existing grid solutions with few tweaks to support
  regular floating elements commonly seen at one-dimensional layouts. However
  any CSS framework cosmetic rules can be used via the provided **Classes**
  options.


# AUTHOR/MAINTAINER/CREDITS
* [Gaus Surahman](https://www.drupal.org/user/159062)
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


## READ MORE
See the project page on drupal.org for more updated info:

[Blazy module](https://drupal.org/project/blazy)
