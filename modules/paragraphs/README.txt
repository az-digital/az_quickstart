README.txt
==========

Paragraph is a module to create paragraphs in your content.
You can create types(with own display and fields) as Paragraph types.

When you use the Entity Reference Paragraphs widget + Entity Reference selection
type on your node/entity, you can select the allowed types, and when using the
widget, you can select a paragraph type from the allowed types to use
different fields/display per paragraph.

* Different fields per paragraph type
* Using different Paragraph types in a single Paragraph field
* Displays per paragraph type

WIDGETS
-------------

Paragraphs currently provides two different widgets that can be used.

 * Legacy (formerly Classic): a stable UI with limited features that will not be
   changed or updated.

 * Stable (formerly Experimental): This widget provides additional features like
   duplicating paragraphs and a drag & drop mode (see below) as well a improved
   user experience. It is now the default and recommended widget, but changes
   between versions are to be expected and customizations might need to be
   updated.

Drag & drop
-------------

The stable widget offers a separate mode that allows to re-sort paragraphs
not just within the same level but it is also possible to change the hierarchy
and move paragraphs including their children around and into other paragraphs.

During drag & drop mode, paragraphs are also displayed as a summary only, which
results in a very compact display that makes it easier to move them around.

Starting with Drupal 8.8.0, the necessary library is part of Drupal core and
this feature is always available.

Use the version 1.10+ as it's tested and approved. Older versions may introduce
bugs with nested drag & drop functionality.

If the file exists, the feature will automatically be available.

MULTILINGUAL CONFIGURATION
-------------
 * Enable the Paragraph module.

 * Add new languages for the translation in Configuration » Languages.

 * Enable any custom content type with a paragraph field to be translatable in
 Configuration » Content language
 and translation:

   - Under Custom language settings check Content.

      - Under Content check the content type with a paragraph field.

   - Make sure that the paragraph field is set to NOT translatable.

   - Set the fields of each paragraph type to translatable as required.

 * Check Paragraphs as the embedded reference in Configuration » Translation
 Management settings.

 * Create a new content - Paragraphed article and translate it.


LIMITATION
-------------
For now, this module does not support switching entity reference revision field
of the paragraph itself into multilingual mode. This would raise complexity
significantly.
Check #2461695: Support translatable paragraph entity reference revision field
(https://www.drupal.org/node/2461695).
