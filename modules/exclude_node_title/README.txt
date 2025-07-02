CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------
This module handles a very simple functionality, decide whatever to exclude a
node title from full node page or node teasers.

It provides a checkbox on node-edit pages for easier exclusion, or you can use
the admin page to manually enter a list of node id's to exclude title.

This module also provides the option to hide all titles of a certain Content
type. From the administrative interface you can select a content type to hide
title for.


REQUIREMENTS
------------
This module requires no modules outside of Drupal core.


INSTALLATION
------------
Install the Exclude Node Title as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
--------------
Let's say you create a content type called: Lightbox content, and in your
layout lightbox content will have titles set on title attribute of the link,
rather than inline on your page, so you would like to exclude title from
displaying inline for all your nodes of type Lightbox content, simple, just
check the option and voila.

This project has some other, very simple approaches, like hiding the title from
CSS using display: none or applying the template_preprocess_page hook in your
theme to make `title` variable null.
Actually `Exclude Node Title` does the same thing, only that you don't have to
manually make different hacks, hard to track from the administration interface.


MAINTAINERS
-----------
 * Neslee Canil Pinto: https://www.drupal.org/u/neslee-canil-pinto
 * Yonas Yanfa: https://www.drupal.org/u/fizk
 * Gabriel Ungureanu: https://www.drupal.org/u/gabrielu
