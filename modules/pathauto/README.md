CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Notices
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------

The Pathauto module provides support functions for other modules to
automatically generate aliases based on appropriate criteria and tokens, with a
central settings path for site administrators.

Implementations are provided for core entity types: content, taxonomy terms,
and users (including blogs and forum pages).

Pathauto also provides a way to delete large numbers of aliases. This feature is
available at Administer > Configuration > Search and metadata > URL aliases >
Delete aliases.

Pathauto is beneficial for search engine optimization (SEO) and for ease-of-use
for visitors.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/pathauto

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/pathauto


REQUIREMENTS
------------

This module requires the following module:

 * Token - https://www.drupal.org/project/token


RECOMMENDED MODULES
-------------------

 * Redirect - https://www.drupal.org/project/redirect
 * Sub-pathauto (Sub-path URL Aliases) -
   https://www.drupal.org/project/subpathauto


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

 1. Configure the module at Administration > Configuration > Search and metadata
    > URL aliases > Patterns (admin/config/search/path/patterns). Add a new
    pattern by clicking "Add Pathauto pattern".
 2. Select the entity type for "Pattern Type", and provide an administrative
    label.
 2. Fill out "Path pattern" with a token replacement pattern, such as
    [node:title]. Use the "Browse available tokens" link to view available
    variables to construct a URL alias pattern.
 3. Click "Save" to save your pattern. When you save new content from now on, it
    will automatically be assigned the pathauto-configured URL alias.


NOTICES
-------

Pathauto adds URL aliases to content, users, and taxonomy terms. Because the
patterns are an alias, the standard Drupal URL (for example node/123 or
taxonomy/term/1) will still function as normal. If you have external links to
your site pointing to standard Drupal URLs, or hardcoded links in a module,
template, content, or menu which point to standard Drupal URLs, it will bypass
the alias set by Pathauto.

There are reasons you might not want two URLs for the same content on your site.
If this applies to you, you will need to update any hard coded links in your
content or blocks.

If you use the "system path" (i.e. node/10) for menu items and settings, Drupal
will replace it with the URL alias.


TROUBLESHOOTING
---------------

Q: Why are URLs not getting replaced with aliases?
A: Only URLs passed through the Drupal URL and Link APIs will be replaced
   with their aliases during page output. If a module or a template contains
   hardcoded links (such as 'href="node/$node->nid"'), those will not get
   replaced with their corresponding aliases.

Q: How do you disable Pathauto for a specific content type (or taxonomy)?
A: When the pattern for a content type is left blank, the default pattern will
   be used. If the default pattern is also blank, Pathauto will be disabled
   for that content type.


MAINTAINERS
-----------

Current maintainers:

 * Dave Reid - http://www.davereid.net
 * Sascha Grossenbacher - https://www.drupal.org/u/berdir

The original Pathauto release combined the functionality of Mike Ryan's autopath
with Tommy Sundstrom's path_automatic. Significant enhancements were contributed
by jdmquin @ www.bcdems.net. Matt England added the (now deprecated) tracker
support. Other suggestions and patches have been contributed by the Drupal
community.
