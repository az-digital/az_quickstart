Schema.org Metatag
--------------------------------------------------------------------------------
This project extends Drupal's Metatag module to display structured data as
JSON-LD in the head of web pages. Either hard-code properties or identify
patterns using token replacements. Using the override system in Metatag module
you can define default structured data values for all content types, override
the global content defaults for a particular content type, or even override
everything else on an individual node to provide specific values for that node.

Read more about Schema.org, JSON-LD, and how this module works in an article on
Lullabot.com:
Create SEO Juice From JSON LD Structured Data in Drupal
https://www.lullabot.com/articles/create-seo-juice-by-adding-json-ld-structured-data-to-drupal-8.

Since the Schema.org [1] list is huge, and growing, this module only provides a
small subset of those values, but it is designed to be extensible. Several types
are included which can be copied to add new types (groups) with any number of
their own properties.

The module creates the following Schema.org object types:

Schema.org/Article
Schema.org/BreadcrumbList
Schema.org/Event
Schema.org/ItemList (for Views)
Schema.org/Organization
Schema.org/Person
Schema.org/Product
Schema.org/Service
Schema.org/VideoObject
Schema.org/WebPage
Schema.org/WebSite


Requirements
--------------------------------------------------------------------------------
The Metatag module is required:
https://www.drupal.org/project/metatag

The module requires PHP 8.0 or newer, as of Schema Metatag v3.0.0 it will not
work with PHP 7.


Validation
--------------------------------------------------------------------------------
For more information and to test the results:
- https://developers.google.com/search/docs/guides/intro-structured-data
- https://schema.org/docs/full.html
- https://search.google.com/structured-data/testing-tool
If you are new to structured data you should definitely read the first reference
carefully.

For more information about the Metatag module and how to set it up, see
https://www.drupal.org/docs/8/modules/metatag.


Known Issues
--------------------------------------------------------------------------------
- To populate the image width and height properties, use the appropriate tokens.
  The core Token module provides image height and width token, for example, the
  token [node:field_image:0:width] will be replaced with the width of the first
  image in field_image on the current node.


Development Instructions
--------------------------------------------------------------------------------
This module defines Metatag groups that map to Schema.org types, and metatag
tags for Schema.org properties, then steps in before the values are rendered as
metatags, pulls the Schema.org values out of the header created by Metatag, and
instead renders them as JSON-LD when the page is displayed.

The module includes a base group class and several base tag classes that can be
extended. Many properties are simple key/value pairs that require nothing more
than extending the base class and giving them their own ids. Some are more
complex, like Person and Organization, and BreadcrumbList, and they collect
multiple values and serialize the results.

The development process for adding groups and properties:

- Create groups at MODULE_NAME/src/Plugins/metatag/Group and properties at
  MODULE_NAME/src/Plugins/metatag/Tag. Each tag extends the appropriate base
  class.

In either case, you should be able to copy one of the existing modules as a
starting point.

There is an included module, Schema.org Article Example, that shows how other
modules can add more properties to types that are already defined.


Examples and Hints
--------------------------------------------------------------------------------
Using this module, the code in the head might end up looking like this:

<code>
<script type="application/ld+json">{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "Article",
            "description": "Curabitur arcu erat, accumsan id imperdiet et.",
            "datePublished": "2009-11-30T13:04:01-0600",
            "dateModified": "2017-05-17T19:02:01-0500",
            "headline": "Curabitur arcu erat]",
            "author": {
                "@type": "Person",
                "name": "Minney Mouse",
                "sameAs": "https://example.com/user/2"
            },
            "publisher": {
                "@type": "Organization",
                "name": "Example.com",
                "sameAs": "https://example.com/",
                "logo": {
                    "@type": "ImageObject",
                    "url": "https://example.com/sites/default/files/logo.png",
                    "width": "600",
                    "height": "60"
                }
            },
            "mainEntityOfPage": {
                "@type": "WebPage",
                "@id": "https://example.com/story/example-story"
            },
        },
    ]
}</script>
</code>


Contributing
--------------------------------------------------------------------------------
The composer.json file should be kept normalized using
ergebnis/composer-normalize:

* composer require --dev ergebnis/composer-normalize
* composer normalize modules/contrib/schema_metatag/composer.json


Credits
--------------------------------------------------------------------------------
The initial development was by Karen Stevenson [2].


References
--------------------------------------------------------------------------------
1: https://schema.org/
2: https://www.drupal.org/u/karens
