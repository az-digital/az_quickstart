# Quickstart Multilingual
The Quickstart Multilingual module enables site editors to provide language translations for their site content. This module only provides the functionality necessary to serve content in multiple languages, and does not provide content translations themselves. It is up to the site owner(s) to provide translations for their site's content. 

## Permissions

- To enable this feature on your site, someone with an `administrator` role must enable this module on your site.
- To install/manage languages on your site, you must have the `content administrator` role.
- To make content translations on your site, you must have the `content editor` role.

## Instructions

### Setup

1. Enable the module.
2. Navigate to `/admin/config/regional/language` and install the desired language(s).
3. Clear site caches.
4. Navigate to `/admin/structure/block` and place the  "Language Switcher" block in the desired region. It is recommended that you place the block above the page title (whether in a separate region, or just have the block display above the page title in the Content region).
   - You can choose to limit the block to specific pages or sections of your site (if you're not translating your entire site).
   - You can choose to place the block site-wide.
   - You can choose to place the block only on specific content types.

### Adding Translations

1. Navigate to a piece of content that you want to translate.
2. Click on the "Translate" tab .
3. Click on "Edit" for the language(s) you wish to add translations for.
   - Add your content translations and save your changes.
4. Use the language toggle block to switch between your content translation(s).

### Important Notes

- [LinkIt does not currently support multilingual functionality](https://www.drupal.org/project/linkit/issues/2886455). Recommend manually copying the path to the page you need to link to, if it's in another language than the site's default.
- If you have enabled any extra QS modules that contain content/paragraph types (such as HTML Field and Carousel Item), you will need to manually enable translations for those entity types after installing this module.
- This module is compatible with Content Moderation workflows, but you will need to manually enable translations on moderation states after installing this module.
