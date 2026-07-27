<?php

namespace Drupal\az_ranking\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElement as RenderElementBase;

/**
 * Provides a render element for one az_ranking field item.
 *
 * Owns building both the editable fields (details) and the live preview
 * (preview_wrapper) together, as one real, reusable Element plugin -
 * instead of the fields being built directly in
 * AZRankingWidget::formElement() with the preview patched on afterwards
 * via #after_build bolted onto Field API's own opaque per-delta wrapper.
 *
 * #after_build is still what rebuilds the preview (see
 * AZRankingWidget::rebuildRankingPreview()) - a #value_callback doesn't fit
 * here, since the preview isn't itself a single resolvable value, it's a
 * render array derived from many sibling fields' already-resolved values.
 * What changes is WHERE that logic lives: a self-contained Element type
 * with direct, local access to its own details/preview_wrapper children
 * (preserving the direct-sibling access #after_build needs - scoping a
 * custom element to the preview alone would lose that, since #after_build
 * only sees its own descendants, not a parent's other children), not glue
 * entangled in widget/Field API internals.
 *
 * @see \Drupal\az_ranking\Plugin\Field\FieldWidget\AZRankingWidget
 * @see https://github.com/az-digital/az_quickstart/pull/5309
 */
#[RenderElement('az_ranking_item')]
class AZRankingItemElement extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#widget' => NULL,
      '#az_item' => NULL,
      '#ranking_defaults' => [],
      '#status' => FALSE,
      '#delta' => 0,
      '#field_name' => '',
      '#process' => [[$class, 'processRankingItem']],
      '#after_build' => [[$class, 'afterBuildRebuildPreview']],
    ];
  }

  /**
   * Builds the details fields and the preview placeholder.
   *
   * Delegates to the widget instance (stashed on #widget by formElement())
   * since building these fields needs several widget instance methods as
   * #element_validate/#after_build callbacks
   * (validateRankingLink()/addAzRankingContextToMediaEdit()/etc.) that
   * only make sense as instance methods, not static ones.
   */
  public static function processRankingItem(array $element, FormStateInterface $form_state, &$complete_form) {
    /** @var \Drupal\az_ranking\Plugin\Field\FieldWidget\AZRankingWidget $widget */
    $widget = $element['#widget'];
    return $widget->buildRankingItemElement($element, $form_state);
  }

  /**
   * Rebuilds the preview from the Form API-populated field values.
   */
  public static function afterBuildRebuildPreview(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\az_ranking\Plugin\Field\FieldWidget\AZRankingWidget $widget */
    $widget = $element['#widget'];
    return $widget->rebuildRankingPreview($element, $form_state);
  }

}
