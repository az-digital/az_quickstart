<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\ProcessPluginBase;

/**
 * Exchange rows and columns.
 *
 * Examples:
 *
 * @code
 * process:
 *   bar:
 *     -
 *       plugin: transpose
 *       source:
 *         - foo0
 *         - foo1
 *         - foo2
 * @endcode
 *
 * This will create an array of 3-element, numerically indexed arrays. Each
 * array will have one element from each of the source properties.
 *
 * @code
 * process:
 *   field_link:
 *     -
 *       plugin: transpose
 *       source:
 *         - link_url
 *         - link_text
 *     -
 *       plugin: sub_process
 *       process:
 *         uri:
 *           -
 *             plugin: extract
 *             source:
 *               - 0
 *             index:
 *               - 0
 *           -
 *             plugin: link_uri
 *             validate_route: false
 *         title:
 *           -
 *             plugin: extract
 *             source:
 *               - 1
 *             index:
 *               - 0
 * @endcode
 *
 * Suppose the source property link_url has the URL for three links, and the
 * source property link_text has the corresponding link text:
 * [url0, url1, url2] and [text0, text1, text2].
 * Then the transpose plugin produces
 * [[url0, text0], [url1, text1], [url2, text2]].
 * Inside sub_process, the extract plugin in this example takes each
 * [url, text] subarray and assigns uri: url, title: text.
 *
 * @MigrateProcessPlugin(
 *   id = "transpose"
 * )
 */
class Transpose extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($table, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Make sure that $table is an array of arrays.
    if (!is_array($table) || $table == []) {
      return [];
    }
    foreach ($table as &$value) {
      $value = (array) $value;
    }

    // @see https://stackoverflow.com/a/47718734/3130080
    return array_map(NULL, ...$table);
  }

}
