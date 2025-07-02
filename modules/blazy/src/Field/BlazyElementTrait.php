<?php

namespace Drupal\blazy\Field;

use Drupal\Core\Render\Markup;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Theme\Attributes;

/**
 * A Trait for blazy element and its captions.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 */
trait BlazyElementTrait {

  /**
   * The svg manager service.
   *
   * @var \Drupal\blazy\Media\Svg\SvgInterface
   */
  protected $svgManager;

  /**
   * Returns the relevant elements based on the configuration.
   *
   * @todo remove caption for captions at 3.x.
   */
  protected function toElement($blazies, array &$data, array $captions = []): array {
    $delta    = $data['#delta'] ?? 0;
    $captions = $captions ?: ($data['captions'] ?? $data['caption'] ?? []);
    $captions = array_filter($captions);

    // @todo remove caption for captions at 3.x.
    unset($data['captions'], $data['caption']);

    // Call manager not formatter due to sub-module deviations.
    $this->manager->verifyItem($data, $delta);

    // Provides inline SVG if applicable.
    $this->viewSvg($data);

    return $this->themeBlazy($data, $captions, $delta);
  }

  /**
   * Provides inline SVG if so-configured.
   *
   * @todo move it into ::getBlazy() for more available data, like title, etc.
   */
  protected function viewSvg(array &$element): void {
    $settings = $this->formatter->toHashtag($element);
    $item     = $this->formatter->toHashtag($element, 'item', NULL);
    $blazies  = $settings['blazies'];
    $inline   = $settings['svg_inline'] ?? FALSE;
    $bg       = $settings['background'] ?? FALSE;
    $exist    = Blazy::svgSanitizerExists();
    $valid    = $inline && $exist && !$bg;

    if ($valid && $uri = $blazies->get('image.uri')) {
      $options = BlazyDefault::toSvgOptions($settings);

      // @todo remove fallback after entities updated, except file which has it.
      $title = $blazies->get('image.title')
        ?: Attributes::altTitle($blazies, $item)['title'];

      if ($title) {
        $options['title'] = Attributes::escape($title, TRUE);
      }

      if ($output = $this->svgManager->view($uri, $options)) {
        $blazies->set('is.unlazy', TRUE)
          ->set('lazy.html', FALSE)
          ->set('use.image', FALSE)
          ->set('use.loader', FALSE);

        $element['content'][] = ['#markup' => Markup::create($output)];
      }
    }
  }

  /**
   * Merges source with element array, excluding renderable array.
   *
   * Since 2.17, $source is no longer accessible downtream for just $element.
   */
  protected function withHashtag(array $source, array $element): array {
    $data = $this->formatter->withHashtag($source);
    return array_merge($data, $element);
  }

  /**
   * Builds the item using theme_blazy(), if so-configured.
   */
  private function themeBlazy(array $data, array $captions, $delta): array {
    $internal = $data;

    // Allows sub-modules to use theme_blazy() as their theme_ITEM() contents.
    if ($texts = $this->toBlazy($internal, $captions, $delta)) {
      $internal['captions'] = $texts;
    }

    $render = $this->formatter->getBlazy($internal);
    $output = $this->withHashtag($data, $render);

    // @todo compare with split below if mergeable even more.
    // Only blazy has content, unset here.
    // unset($data['content']);
    // $element = $data;
    // $element[static::$itemId] = $blazy;
    // Inform thumbnails with the blazy processed settings.
    // $this->formatter->postBlazy($element, $blazy);
    if (static::$namespace == 'blazy') {
      $element = $output;
    }
    else {
      // Only blazy has content, unset here.
      unset($data['content']);

      $element = $data;
      $element[static::$itemId] = $output;

      // Inform thumbnails with the blazy processed settings.
      $this->formatter->postBlazy($element, $output);
    }
    return $element;
  }

  /**
   * Provides relevant attributes to feed into theme_blazy().
   */
  private function toBlazy(array &$data, array &$captions, $delta): array {
    // Call manager not formatter due to sub-module deviations.
    $this->manager->toBlazy($data, $captions, $delta);
    return $captions;
  }

}
