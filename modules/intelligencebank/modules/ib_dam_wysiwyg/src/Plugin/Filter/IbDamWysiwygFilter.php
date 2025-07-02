<?php

namespace Drupal\ib_dam_wysiwyg\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * The filter to turn tokens inserted into the WYSIWYG into assets.
 *
 * @Filter(
 *   title = @Translation("IntelligenceBank DAM WYSIWYG"),
 *   id = "ib_dam_wysiwyg",
 *   description = @Translation("Enables the use of IntelligenceBank DAM WYSIWYG. <br/><strong>DEPRECATED in 4.x and will be removed in 5.x</strong>"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class IbDamWysiwygFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    return new FilterProcessResult($text);
  }

}
