<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a paragraph behavior for setting display types.
 *
 * @ParagraphsBehavior(
 *   id = "az_multivalue_grid_views",
 *   label = @Translation("Quickstart Paragraph Behavior for Views to allow different grid layouts for each item in a multi-value field."),
 *   description = @Translation("Provides settings that allow changing display of a multivalue views reference field."),
 *   weight = 0
 * )
 */
class AZMulitvalueGridViews extends ParagraphsBehaviorBase {

  /**
   * Drupal\Core\Entity\EntityDisplayRepositoryInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Asset\LibraryDiscoveryInterface definition.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->entityDisplayRepository = $container->get('entity_display.repository');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->libraryDiscovery = $container->get('library.discovery');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {

    // Get plugin configuration.
    $config = $this->getSettings($paragraph);
    // Apply bottom spacing if set.
    if (!empty($config['settings']['display'])) {
      $build['#attributes']['class'][] = $config['settings']['display'];
    }
    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }


  /**
   * Get this plugins Behavior settings.
   *
   * @return array
   *   Behavior settings.
   */
  protected function getSettings(ParagraphInterface $paragraph) {
    $settings = $paragraph->getAllBehaviorSettings();
    return $settings[$this->pluginId] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {

    // Get stored plugin configuration for this paragraph.
    $config = $this->getSettings($paragraph);

    // Provide detail container for settings.
    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional layout options'),
      '#open' => FALSE,
    ];

    // Use saved bottom spacing if available.
    $default_display = (!empty($config['settings']['display'])) ?
      $config['settings']['display'] : 'col';

    // Default Bottom Spacing settings.
    $form['settings']['display'] = [
      '#title' => $this->t('Side by side display'),
      '#type' => 'select',
      '#options' => [
        'col' => $this->t('Side by side'),
        'col-12' => $this->t('Stacked'),
      ],
      '#default_value' => $default_display,
      '#description' => $this->t('test.'),
      '#weight' => 10,
    ];

    // This places the form fields on the content tab rather than behavior tab.
    // There may be a more official API for this in the future.
    // Note that form is passed by reference.
    // @see https://www.drupal.org/project/paragraphs/issues/2928759
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(&$variables) {

    // Libraries to attach to this paragraph.
    $libraries = [];

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];

    // Get plugin configuration.
    $config = $this->getSettings($paragraph);

    // Get the paragraph bundle name and compute name of potential library.
    $bundle = $paragraph->bundle();
    $libraries[] = 'az_paragraphs.' . $bundle;

    // Generate library names to check based on view mode.
    if (!empty($variables['view_mode'])) {
      // Potential library for paragraph view mode.
      $libraries[] = 'az_paragraphs.' . $variables['view_mode'];
      // Bundle-specific view mode libraries.
      $libraries[] = 'az_paragraphs.' . $bundle . '_' . $variables['view_mode'];
    }
    $variables['az_multivalue_grid_views'] = $config;

    // Check if any of the potential library names actually exist.
    foreach ($libraries as $name) {
      // Check if library discovery service knows about the library.
      $library = $this->libraryDiscovery->getLibraryByName('az_paragraphs', $name);

      if ($library) {
        // If we found a library, attach it to the paragraph.
        $variables['#attached']['library'][] = 'az_paragraphs/' . $name;
      }
    }
    $az_multivalue_grid_views_settings = $variables['az_multivalue_grid_views'];
    $variables['content']['field_az_view_reference'][0]['az_multivalue_grid_views'] = $az_multivalue_grid_views_settings;

  }
}
