<?php

namespace Drupal\viewsreference\Plugin\Field\FieldFormatter;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field formatter for Viewsreference Field.
 *
 * @FieldFormatter(
 *   id = "viewsreference_label_formatter",
 *   label = @Translation("Label"),
 *   description = @Translation("Display the label of the referenced view."),
 *   field_types = {"viewsreference"}
 * )
 */
class ViewsReferenceLabelFormatter extends FormatterBase {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['link' => TRUE] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['link'] = [
      '#title' => t('Link label to the referenced view'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('link') ? t('Link to the referenced view') : t('No link');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output_as_link = $this->getSetting('link');

    if (!$this->moduleHandler->moduleExists('views_ui') ||
      !$this->currentUser->hasPermission('administer views')) {
      $output_as_link = FALSE;
    }

    foreach ($items as $delta => $item) {
      $view_id = $item->getValue()['target_id'];
      $display_id = $item->getValue()['display_id'];
      $view = Views::getView($view_id);
      // Add an extra check because the view could have been deleted.
      if (!($view instanceof ViewExecutable)) {
        $elements[$delta] = ['#plain_text' => $this->t('Missing view: %view_id', ['%view_id' => $view_id])];
        continue;
      }

      if ($view->setDisplay($display_id)) {
        $label = $this->t('@view_name - @display_title', [
          '@view_name' => $view->storage->label(),
          '@display_title' => $view->getDisplay()->display['display_title'],
        ]);
      }
      // The view display could have been removed.
      else {
        $label = $this->t('@view_name - Missing display: %display_id', [
          '@view_name' => $view->storage->label(),
          '%display_id' => $display_id,
        ]);
      }

      if ($output_as_link) {
        if ($view->setDisplay($display_id)) {
          $uri = Url::fromRoute('entity.view.edit_display_form', ['view' => $view_id, 'display_id' => $display_id]);
        }
        else {
          $uri = Url::fromRoute('entity.view.edit_display_form', ['view' => $view_id]);
        }

        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $label,
          '#url' => $uri,
          '#options' => $uri->getOptions(),
          '#attributes' => [
            'target' => '_blank',
          ],
        ];

        // Move field item attributes to the link element.
        if (!empty($items[$delta]->_attributes)) {
          $elements[$delta]['#options'] += ['attributes' => []];
          $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
          unset($items[$delta]->_attributes);
        }
      }
      else {
        $elements[$delta] = ['#plain_text' => $label];
      }
    }
    return $elements;
  }

}
