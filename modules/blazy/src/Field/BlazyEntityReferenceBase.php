<?php

namespace Drupal\blazy\Field;

use Drupal\blazy\BlazyDefault;

/**
 * Base class for all entity reference formatters with field details.
 *
 * The most robust formatter at field level, more than BlazyEntityMediaBase, to
 * support nested/ overlayed formatters like seen at Slick/ Splide Paragraphs
 * formatters which is not supported at BlazyEntityMediaBase to avoid
 * complication -- embedding entities within Media, although fine and possible.
 *
 * @see \Drupal\slick\Plugin\Field\FieldFormatter\SlickEntityReferenceFormatterBase
 * @see \Drupal\splide\Plugin\Field\FieldFormatter\SplideEntityReferenceFormatterBase
 */
abstract class BlazyEntityReferenceBase extends BlazyEntityMediaBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::extendedSettings()
      + BlazyDefault::gridSettings()
      + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function getScopedDefinition(array $form): array {
    $definition   = parent::getScopedDefinition($form);
    $existings    = $definition['additional_descriptions'] ?? [];
    $descriptions = [
      'layout' => [
        'description' => $this->t('Create a dedicated List (text - max number 1) field related to the caption placement to have unique layout per slide with the following supported keys: top, right, bottom, left, center, center-top, etc. Be sure its formatter is Key.'),
        'placement' => 'before',
      ],
      'overlay' => [
        'description' => $this->t('The formatter/renderer is managed by the child formatter.'),
        'placement' => 'after',
      ],
    ];

    $definition['additional_descriptions'] = $this->manager->merge($descriptions, $existings);
    return $definition;
  }

  /**
   * {@inheritdoc}
   *
   * This method is used but not called by sub-modules. Not used by blazy.
   */
  protected function withElementExtra(array &$element): void {
    parent::withElementExtra($element);

    // @todo remove helper at/ by 3.x post migrations:
    $this->formatter->hashtag($element);

    $settings = &$element['#settings'];
    $entity   = $element['#entity'];
    $langcode = $element['#langcode'];
    $_class   = $settings['class'] ?? NULL;
    $_layout  = $settings['layout'] ?? NULL;

    // Anything below basically replacing useless field_NAME with its value.
    // Layouts can be builtin, or field, if so configured.
    if ($_layout) {
      $layout = $_layout;
      if (strpos($layout, 'field_') !== FALSE && isset($entity->{$layout})) {
        $layout = $this->getString($entity, $layout, $langcode);
      }
      $settings['layout'] = $layout;
    }

    // Classes, if so configured.
    if ($_class && isset($entity->{$_class})) {
      $settings['class'] = $this->getString($entity, $_class, $langcode);
    }
  }

  /**
   * Builds the captions.
   */
  protected function getCaptions(array $element): array {
    $captions = parent::getCaptions($element);

    [
      '#settings' => $settings,
      '#entity'   => $entity,
    ] = $element;

    $view_mode = $settings['view_mode'] ?? 'full';
    $_overlay  = $settings['overlay'] ?? NULL;

    // Overlay, like slider or video over slider, if so configured.
    if ($_overlay && isset($entity->{$_overlay})) {
      $captions['overlay'] = $entity->get($_overlay)->view($view_mode);
    }

    return array_filter($captions);
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    $parent   = parent::getPluginScopes();
    $_strings = ['text', 'string', 'list_string'];
    $strings  = $this->getFieldOptions($_strings);

    return [
      'classes' => $strings,
      'images'  => $this->getFieldOptions(['image']),
      'layouts' => $strings,
      'vanilla' => TRUE,
    ] + $parent;
  }

}
