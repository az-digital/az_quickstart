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
class AZDefaultParagraphBehavior extends ParagraphsBehaviorBase {

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
    $instance = new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity_field.manager')
    );
    $instance->libraryDiscovery = $container->get('library.discovery');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityDisplayRepository = $container->get('entity_display.repository');
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
    return isset($settings[$this->pluginId]) ? $settings[$this->pluginId] : [];
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
      '#title' => t('Additional options'),
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
      '#description' => $this->t('More detail on spacing can be found in the AZ Bootstrap documentation.'),
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

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];

    // Get plugin configuration.
    $config = $this->getSettings($paragraph);

    // Get the paragraph bundle name and compute name of potential library.
    $bundle = $paragraph->bundle();
    $name = 'az_paragraphs.' . $bundle;

    // Check if az_paragraphs implements library for the  paragraph bundle.
    $library = $this->libraryDiscovery->getLibraryByName('az_paragraphs', $name);

    // If we found a library, attach it to the paragraph.
    if ($library) {
      $variables['#attached']['library'][] = 'az_paragraphs/' . $name;
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
      $build['#attributes']['class'] = $config['az_display_settings']['bottom_spacing'];
    }

  }

}
