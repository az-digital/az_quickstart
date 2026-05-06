<?php

declare(strict_types=1);

namespace Drupal\az_opportunity_trellis\Plugin\migrate\process;

use Drupal\Component\Utility\Html;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Extracts a plain URL from a value that may be a raw URL or an HTML anchor.
 *
 * When the Trellis API returns Application_Form_URL__c as an <a href="...">
 * element instead of a bare URL, this plugin extracts the href attribute value.
 *
 * @code
 * process:
 *   field_az_application_link/uri:
 *     plugin: az_extract_url
 *     source: application_form_url
 * @endcode
 */
#[MigrateProcess('az_extract_url')]
class AZExtractUrl extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value) || !is_string($value)) {
      return $value;
    }

    // If the value contains an HTML anchor tag, extract the href.
    if (str_contains($value, '<a ')) {
      $doc = Html::load($value);
      $xpath = new \DOMXPath($doc);
      $href = $xpath->evaluate('string(//a/@href)');
      if (!empty($href)) {
        return Html::decodeEntities($href);
      }
      // Anchor found but no usable href — return empty so the link field is skipped.
      return '';
    }

    return $value;
  }

}
