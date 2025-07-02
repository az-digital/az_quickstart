<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * This base plugin allows "link rel" tags to be further customized.
 */
abstract class LinkRelBase extends MetaNameBase {

  /**
   * {@inheritdoc}
   */
  protected $htmlTag = 'link';

  /**
   * {@inheritdoc}
   */
  protected $htmlNameAttribute = 'rel';

  /**
   * {@inheritdoc}
   */
  protected $htmlValueAttribute = 'href';

}
