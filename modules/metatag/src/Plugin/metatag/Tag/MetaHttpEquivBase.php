<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * This base plugin allows "http-equiv"-style meta tags to be customized.
 *
 * Previously used with e.g. the content language meta tag.
 */
abstract class MetaHttpEquivBase extends MetaNameBase {

  /**
   * {@inheritdoc}
   */
  protected $nameAttribute = 'http-equiv';

  /**
   * {@inheritdoc}
   */
  protected $htmlNameAttribute = 'http-equiv';

}
