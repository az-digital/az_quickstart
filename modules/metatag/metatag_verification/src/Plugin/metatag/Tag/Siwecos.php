<?php

namespace Drupal\metatag_verification\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'siwecostoken' meta tag.
 *
 * @MetatagTag(
 *   id = "siwecos",
 *   label = @Translation("SIWECOS"),
 *   description = @Translation("A string provided by <a href=':siwecos'>SIWECOS</a>, the free website security scanner.", arguments = {":siwecos"  = "https://siwecos.de/"}),
 *   name = "siwecostoken",
 *   group = "site_verification",
 *   weight = 7,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Siwecos extends MetaNameBase {
}
