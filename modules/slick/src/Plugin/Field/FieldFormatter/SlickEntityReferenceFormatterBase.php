<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\blazy\Field\BlazyEntityReferenceBase;
use Drupal\slick\SlickDefault;

/**
 * Base class for slick entity reference formatters with field details.
 *
 * @see \Drupal\slick_media\Plugin\Field\FieldFormatter
 * @see \Drupal\slick_paragraphs\Plugin\Field\FieldFormatter
 */
abstract class SlickEntityReferenceFormatterBase extends BlazyEntityReferenceBase {

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
  protected static $fieldType = 'entity';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SlickDefault::extendedSettings() + parent::defaultSettings();
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
    $settings  = $this->formatter->toHashtag($element);
    $entity    = $element['#entity'];
    $delta     = $element['#delta'];
    $item      = $this->formatter->toHashtag($element, 'item', NULL);
    $view_mode = $settings['view_mode'] ?? '';
    $_caption  = $settings['thumbnail_caption'] ?? NULL;
    $captions  = [];

    if ($_caption) {
      if ($item && $text = trim($item->{$_caption} ?? '')) {
        $captions = ['#markup' => Xss::filterAdmin($text)];
      }
      else {
        $captions = $this->viewField($entity, $_caption, $view_mode);
      }
    }

    // Thumbnail usages: asNavFor pagers, dot, arrows thumbnails.
    $tn = $this->formatter->getThumbnail($settings, $item, $captions);
    $build[static::$navId]['items'][$delta] = $tn;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    $_texts = ['text', 'text_long', 'string', 'string_long', 'link'];
    $texts  = $this->getFieldOptions($_texts);

    return [
      'thumb_captions'  => $texts,
      'thumb_positions' => TRUE,
      'nav'             => TRUE,
    ] + parent::getPluginScopes();
  }

}
