<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the `Blazy File` or `Blazy Image`.
 *
 * Since 2.17, sub-modules can re-use this if similar to ::buildElements():
 * \Drupal\gridstack\Plugin\Field\FieldFormatter\GridStackFileFormatterBase
 * \Drupal\mason\Plugin\Field\FieldFormatter\MasonFileFormatterBase.
 *
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFileFormatter
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyImageFormatter
 */
class BlazyFormatterBlazy extends BlazyFileSvgFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'blazy';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'content';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'blazy';

  /**
   * {@inheritdoc}
   *
   * @todo make it caption similar to sub-modules for easy 3.x migrations.
   */
  protected static $captionId = 'captions';

}
