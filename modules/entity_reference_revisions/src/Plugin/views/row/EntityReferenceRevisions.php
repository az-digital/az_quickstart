<?php

namespace Drupal\entity_reference_revisions\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\Fields;

/**
 * EntityReferenceRevisions row plugin.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "entity_reference_revisions",
 *   title = @Translation("Entity Reference inline fields"),
 *   help = @Translation("Displays the fields with an optional template."),
 *   theme = "views_view_fields",
 *   register_theme = FALSE,
 *   display_types = {"entity_reference"}
 * )
 */
class EntityReferenceRevisions extends Fields {

  /**
   * Overrides \Drupal\views\Plugin\views\row\Fields::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['separator'] = array('default' => '-');

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\row\Fields::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Expand the description of the 'Inline field' checkboxes.
    $form['inline']['#description'] .= '<br />' . $this->t("<strong>Note:</strong> In 'Entity Reference' displays, all fields will be displayed inline unless an explicit selection of inline fields is made here." );
  }

  /**
   * {@inheritdoc}
   */
  public function preRender($row) {
    // Force all fields to be inline by default.
    if (empty($this->options['inline'])) {
      $fields = $this->view->getHandlers('field', $this->displayHandler->display['id']);
      $names = array_keys($fields);
      $this->options['inline'] = array_combine($names, $names);
    }

    return parent::preRender($row);
  }
}
