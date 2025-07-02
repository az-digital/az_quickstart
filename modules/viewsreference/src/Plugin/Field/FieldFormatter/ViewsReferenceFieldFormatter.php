<?php

namespace Drupal\viewsreference\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Field formatter for Viewsreference Field.
 *
 * @FieldFormatter(
 *   id = "viewsreference_formatter",
 *   label = @Translation("Views reference"),
 *   field_types = {"viewsreference"}
 * )
 */
class ViewsReferenceFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();
    $options['plugin_types'] = ['block'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $types = Views::pluginList();
    $options = [];
    foreach ($types as $key => $type) {
      if ('display' === $type['type']) {
        $options[str_replace('display:', '', $key)] = $type['title']->render();
      }
    }
    $form['plugin_types'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('View display plugins to allow'),
      '#default_value' => $this->getSetting('plugin_types'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $allowed = [];
    $settings = $this->getSettings();
    foreach ($settings['plugin_types'] as $type) {
      if ($type) {
        $allowed[] = $type;
      }
    }
    $summary[] = $this->t('Allowed plugins: @view', ['@view' => implode(', ', $allowed)]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Get the information for the parent entity and field to allow
    // customizing the view / view results.
    $parent_entity_type = $items->getEntity()->getEntityTypeId();
    $parent_entity_id = $items->getEntity()->id();
    $parent_field_name = $items->getFieldDefinition()->getName();
    $parent_revision_id = NULL;
    if ($items->getEntity() instanceof RevisionableInterface) {
      $parent_revision_id = $items->getEntity()->getRevisionId();
    }

    $cacheability = new CacheableMetadata();

    foreach ($items as $delta => $item) {
      $view_name = $item->getValue()['target_id'];
      $display_id = $item->getValue()['display_id'];

      // Since no JS creating a node is a multi-step, it is possible that
      // no display ID has yet been selected.
      if (!$display_id) {
        continue;
      }
      $view = Views::getView($view_name);

      // Add an extra check because the view could have been deleted.
      if (!$view instanceof ViewExecutable) {
        continue;
      }

      $view->setDisplay($display_id);
      $enabled_settings = array_filter($this->getFieldSetting('enabled_settings') ?? []);

      // Add properties to the view so our hook_views_pre_build() implementation
      // can alter the view. This is pretty hacky, but we need this to fix ajax
      // behaviour in views. The hook_views_pre_build() needs to know if the
      // view was part of a viewsreference field or not.
      $view->element['#viewsreference'] = [
        'data' => !empty($item->getValue()['data']) ? unserialize($item->getValue()['data'], ['allowed_classes' => FALSE]) : [],
        'enabled_settings' => $enabled_settings,
        'parent_entity_type' => $parent_entity_type,
        'parent_entity_id' => $parent_entity_id,
        'parent_field_name' => $parent_field_name,
        'parent_revision_id' => $parent_revision_id,
        'field_item_delta' => $delta,
      ];

      $view->preExecute();
      $view->execute($display_id);

      // Exposed form handler.
      /** @var \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase $exposed_form_handler */
      $exposed_form_handler = $view->display_handler->getPlugin('exposed_form');

      $render_array = $view->buildRenderable($display_id, $view->args, FALSE);
      if (!empty(array_filter($this->getSetting('plugin_types')))) {
        // Show view if there are results, empty behaviour defined, exposed
        // widgets, or a header or footer set to appear despite no results.
        if (!empty($view->result) || !empty($view->empty) || ($exposed_form_handler?->options['input_required'] ?? FALSE) || !empty($view->exposed_widgets) || !empty($view->header) || !empty($view->footer)) {
          // Add a custom template if the title is available.
          $title = $view->getTitle();
          if (!empty($title)) {
            // If the title contains tokens, we need to render the view to
            // populate the rowTokens.
            if (mb_strpos($title, '{{') !== FALSE) {
              $view->render();
              $title = $view->getTitle();
            }
            $render_array['title'] = [
              '#theme' => $view->buildThemeFunctions('viewsreference__view_title'),
              '#title' => $title,
              '#view' => $view,
            ];
          }
          // The views_add_contextual_links() function needs the following
          // information in the render array in order to attach the contextual
          // links to the view.
          $render_array['#view_id'] = $view->storage->id();
          $render_array['#view_display_show_admin_links'] = $view->getShowAdminLinks();
          $render_array['#view_display_plugin_id'] = $view->getDisplay()->getPluginId();
          views_add_contextual_links($render_array, $render_array['#view_display_plugin_id'], $display_id);

          $elements[$delta]['contents'] = $render_array;
        }
        else {
          // We should always add the cache metadata.
          $elements[$delta]['contents']['#cache'] = $render_array['#cache'];
        }
      }

      // Collect cache metadata of the fully processed view, even if no results.
      $cacheable_metadata = CacheableMetadata::createFromRenderArray($render_array);
      $cacheability->merge($cacheable_metadata);
    }

    $cacheability->applyTo($elements);

    return $elements;
  }

}
