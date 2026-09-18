<?php

namespace Drupal\az_ranking\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElementBase;

/**
 * Provides a render element for one az_ranking field item.
 *
 * One element builds both halves of a ranking's row in the edit form: the
 * editable fields (details) and the live preview (preview_wrapper).
 * Previously the fields were built in AZRankingWidget::formElement() and
 * the preview was attached afterwards, through #after_build on the wrapper
 * Field API generates for each delta.
 *
 * #after_build still does the rebuilding (see
 * AZRankingWidget::rebuildRankingPreview()) - a #value_callback wouldn't
 * fit, since the preview isn't one resolvable value but a render array
 * built from many sibling fields. What changed is where that callback
 * sits. It needs those siblings' resolved values, and #after_build only
 * sees its own descendants, so it has to hang off a parent of both
 * halves. This element is that parent.
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
   * Hands off to the widget instance stashed on #widget by formElement().
   * Building the fields needs several widget methods as #element_validate
   * and #after_build callbacks - validateRankingLink(),
   * addAzRankingContextToMediaEdit() and so on - and those are instance
   * methods, not static ones.
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
