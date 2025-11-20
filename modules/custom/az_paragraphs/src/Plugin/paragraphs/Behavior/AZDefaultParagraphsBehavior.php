<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base behavior for Quickstart Paragraph Defaults.
 *
 * @ParagraphsBehavior(
 *   id = "az_default_paragraph_behavior",
 *   label = @Translation("Default Quickstart Paragraph Behavior"),
 *   description = @Translation("Provides default Quickstart paragraph settings."),
 *   weight = 0
 * )
 */
class AZDefaultParagraphsBehavior extends ParagraphsBehaviorBase {

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

    // Provide detail container for default settings.
    $form['az_display_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional options'),
      '#open' => FALSE,
    ];

    // Use saved bottom spacing if available.
    $default_bottom_spacing = (!empty($config['az_display_settings']['bottom_spacing'])) ?
      $config['az_display_settings']['bottom_spacing'] : 'mb-0';

    // Default Bottom Spacing settings.
    $form['az_display_settings']['bottom_spacing'] = [
      '#title' => $this->t('Bottom Spacing'),
      '#type' => 'select',
      '#options' => [
        'mb-0' => $this->t('Zero'),
        'mb-1' => $this->t('1 (0.25rem | ~4px)'),
        'mb-2' => $this->t('2 (0.5rem | ~8px)'),
        'mb-3' => $this->t('3 (1.0rem | ~16px)'),
        'mb-4' => $this->t('4 (1.5rem | ~24px)'),
        'mb-5' => $this->t('5 (3.0rem | ~48px)'),
        'mb-6' => $this->t('6 (4.0rem | ~64px)'),
        'mb-7' => $this->t('7 (5.0rem | ~80px)'),
        'mb-8' => $this->t('8 (6.0rem | ~96px)'),
        'mb-9' => $this->t('9 (7.0rem | ~112px)'),
        'mb-10' => $this->t('10 (8.0rem | ~128px)'),
      ],
      '#default_value' => $default_bottom_spacing,
      '#description' => $this->t('More detail on spacing can be found in the <a href="https://digital.arizona.edu/arizona-bootstrap/docs/2.0/utilities/spacing/" target="_blank">AZ Bootstrap documentation</a>.'),
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

    // Check if any of the potential library names actually exist.
    foreach ($libraries as $name) {
      // Check if library discovery service knows about the library.
      $library = $this->libraryDiscovery->getLibraryByName('az_paragraphs', $name);

      if ($library) {
        // If we found a library, attach it to the paragraph.
        $variables['#attached']['library'][] = 'az_paragraphs/' . $name;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {

    // Get plugin configuration.
    $config = $this->getSettings($paragraph);

    // Apply bottom spacing if set.
    if (!empty($config['az_display_settings']['bottom_spacing'])) {
      $build['#attributes']['class'][] = $config['az_display_settings']['bottom_spacing'];
    }

    // Add .container class to content-width paragraphs.
    if ((empty($config['full_width']) &&
        (empty($config['text_background_full_width']) || $config['text_background_full_width'] !== 'full-width-background')) ||
        $config['full_width'] !== 'full-width-background') {
      $build['#attributes']['class'][] = 'container';
    }
  }

}
