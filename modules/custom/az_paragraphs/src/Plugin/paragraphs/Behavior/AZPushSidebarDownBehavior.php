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
 *   id = "az_push_sidebar_down",
 *   label = @Translation("Quickstart Push Sidebar Down Behavior"),
 *   description = @Translation("Provides ability to push sidebar down below a
 *   specific paragraph."),
 *   weight = 0
 * )
 */
class AZPushSidebarDownBehavior extends ParagraphsBehaviorBase {

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
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'push_sidebar_down' => '',
    ];
  }

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

    $form['push_sidebar_down'] = [
      '#title' => $this->t('Push sidebar down below this element.'),
      '#type' => 'select',
      '#type' => 'checkbox',
      '#default_value' => $config['push_sidebar_down'],
      '#description' => $this->t('Pushes sidebar down if checked.'),
      '#return_value' => 'push-sidebar-down',
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
    $variables['#attached']['library'][] = 'az_paragraphs/az_paragraphs.push_sidebar_down';
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {

    // Get plugin configuration.
    $config = $this->getSettings($paragraph);

    // Apply bottom spacing if set.
    if (!empty($config['push_sidebar_down'])) {
      $build['#attributes']['class'][] = 'push-sidebar-down';
    }
    if (!empty($config['push_sidebar_down'] && $config['push_sidebar_down'] === 'push-sidebar-down')) {
      $build['#attributes']['push-sidebar-down'] = 'push-sidebar-down';
    }
  }

}
