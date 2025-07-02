<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFileFormatterBase;
use Drupal\slick\SlickDefault;

/**
 * Base class for slick image and file ER formatters.
 *
 * @todo extends BlazyFileSvgFormatterBase post blazy:2.17, or split.
 */
abstract class SlickFileFormatterBase extends BlazyFileFormatterBase {

  use SlickFormatterTrait;

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'slick';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'slide';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'slide';

  /**
   * {@inheritdoc}
   */
  protected static $captionId = 'caption';

  /**
   * {@inheritdoc}
   */
  protected static $navId = 'thumb';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SlickDefault::imageSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function withElementOverride(array &$build, array $element): void {
    // If ($build['#vanilla']) {
    // Build media item including custom highres video thumbnail.
    // @todo re-check/ refine for Paragraphs, etc.
    // $this->blazyOembed->build($element);
    // }
    if (!$build['#asnavfor']) {
      return;
    }

    // The settings in $element has updated metadata extracted from media.
    $settings = $this->formatter->toHashtag($element);
    $item     = $this->formatter->toHashtag($element, 'item', NULL);
    $_caption = $settings['thumbnail_caption'] ?? NULL;
    $caption  = [];

    if ($_caption && $item && $text = $item->{$_caption} ?? NULL) {
      $caption = ['#markup' => Xss::filterAdmin($text)];
    }

    // Thumbnail usages: asNavFor pagers, dot, arrows thumbnails.
    $tn = $this->formatter->getThumbnail($settings, $item, $caption);
    $build[static::$navId]['items'][] = $tn;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    $captions = ['title' => $this->t('Title'), 'alt' => $this->t('Alt')];

    return [
      'namespace'       => 'slick',
      'nav'             => TRUE,
      'thumb_captions'  => $captions,
      'thumb_positions' => TRUE,
    ] + parent::getPluginScopes();
  }

}
