<?php

namespace Drupal\az_core\Plugin\Filter;

use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\az_core\Utility\AZBootstrapMarkupConverter;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a filter to convert legacy AZ Bootstrap 2 attributes.
 */
#[Filter(
  id: "az_bootstrap2_to_az_bootstrap5",
  title: new TranslatableMarkup("Convert AZ Bootstrap 2 to AZ Bootstrap 5"),
  description: new TranslatableMarkup("This filter converts AZ Bootstrap 2 classes to AZ Bootstrap 5 classes."),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
)]
class AzBootstrap2ToAzBootstrap5 extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $output = AZBootstrapMarkupConverter::convert($text);
    if (!$output) {
      return new FilterProcessResult($text);
    }

    return new FilterProcessResult(Markup::create($output));
  }

}
