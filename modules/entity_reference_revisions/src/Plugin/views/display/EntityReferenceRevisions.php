<?php

namespace Drupal\entity_reference_revisions\Plugin\views\display;

use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * The plugin that handles an EntityReferenceRevisions display.
 *
 * "entity_reference_revisions_display" is a custom property, used with
 * \Drupal\views\Views::getApplicableViews() to retrieve all views with a
 * 'Entity Reference Revisions' display.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "entity_reference_revisions",
 *   title = @Translation("Entity Reference Revisions"),
 *   admin = @Translation("Entity Reference Revisions Source"),
 *   help = @Translation("Selects referenceable entities for an entity reference revisions field."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   uses_menu_links = FALSE,
 *   entity_reference_revisions_display = TRUE
 * )
 */
class EntityReferenceRevisions extends DisplayPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$useAJAX.
   */
  protected $usesAJAX = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesPager.
   */
  protected $usesPager = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesAttachments.
   */
  protected $usesAttachments = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Force the style plugin to 'entity_reference_style' and the row plugin to
    // 'fields'.
    $options['style']['contains']['type'] = array('default' => 'entity_reference_revisions');
    $options['defaults']['default']['style'] = FALSE;
    $options['row']['contains']['type'] = array('default' => 'entity_reference_revisions');
    $options['defaults']['default']['row'] = FALSE;

    // Make sure the query is not cached.
    $options['defaults']['default']['cache'] = FALSE;

    // Set the display title to an empty string (not used in this display type).
    $options['title']['default'] = '';
    $options['defaults']['default']['title'] = FALSE;

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::optionsSummary().
   *
   * Disable 'cache' and 'title' so it won't be changed.
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);
    unset($options['query']);
    unset($options['title']);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'entity_reference_revisions';
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::execute().
   */
  public function execute() {
    return $this->view->render($this->display['id']);
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::render().
   */
  public function render() {
    if (!empty($this->view->result) && $this->view->style_plugin->evenEmpty()) {
      return $this->view->style_plugin->render($this->view->result);
    }
    return '';
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::usesExposed().
   */
  public function usesExposed() {
    return FALSE;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::query().
   */
  public function query() {
    if (!empty($this->view->live_preview)) {
      return;
    }

    // Make sure the id field is included in the results.
    $id_field = $this->view->storage->get('base_field');
    $this->id_field_alias = $this->view->query->addField($this->view->storage->get('base_table'), $id_field);

    $options = $this->getOption('entity_reference_revisions_options');

    // Restrict the autocomplete options based on what's been typed already.
    $db = \Drupal::database();
    if (isset($options['match'])) {
      $style_options = $this->getOption('style');
      $value = $db->escapeLike($options['match']) . '%';
      if ($options['match_operator'] != 'STARTS_WITH') {
        $value = '%' . $value;
      }

      // Multiple search fields are OR'd together.
      $conditions = $db->condition('OR');

      // Build the condition using the selected search fields.
      foreach ($style_options['options']['search_fields'] as $field_alias) {
        if (!empty($field_alias)) {
          // Get the table and field names for the checked field.
          $field = $this->view->query->fields[$this->view->field[$field_alias]->field_alias];
          // Add an OR condition for the field.
          $conditions->condition($field['table'] . '.' . $field['field'], $value, 'LIKE');
        }
      }

      $this->view->query->addWhere(0, $conditions);
    }

    // Add an IN condition for validation.
    if (!empty($options['ids'])) {
      $this->view->query->addWhere(0, $id_field, $options['ids']);
    }

    $this->view->setItemsPerPage($options['limit']);
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::validate().
   */
  public function validate() {
    $errors = parent::validate();
    // Verify that search fields are set up.
    $style = $this->getOption('style');
    if (!isset($style['options']['search_fields'])) {
      $errors[] = $this->t('Display "@display" needs a selected search fields to work properly. See the settings for the Entity Reference Revisions list format.', array('@display' => $this->display['display_title']));
    }
    else {
      // Verify that the search fields used actually exist.
      $fields = array_keys($this->handlers['field']);
      foreach ($style['options']['search_fields'] as $field_alias => $enabled) {
        if ($enabled && !in_array($field_alias, $fields)) {
          $errors[] = $this->t('Display "@display" uses field %field as search field, but the field is no longer present. See the settings for the Entity Reference Revisions list format.', array('@display' => $this->display['display_title'], '%field' => $field_alias));
        }
      }
    }
    return $errors;
  }
}
