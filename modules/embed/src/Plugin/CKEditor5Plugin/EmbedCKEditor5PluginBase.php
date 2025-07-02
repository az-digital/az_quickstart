<?php

declare(strict_types=1);

namespace Drupal\entity_embed\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\embed\Controller\EmbedController;
use Drupal\embed\EmbedButtonInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for embed CKEditor5 plugins.
 *
 * When using your annotation should look something like this:
 *
 * @code
 *   id = "your_embed_type_plugin_id",
 *   label = @Translation("Your label"),
 *   ckeditor5 = @CKEditor5AspectsOfCKEditor5Plugin(
 *     plugins = {"yourModule.YourEmbedTypePluginId"},
 *     config = {},
 *   ),
 *   drupal = @DrupalAspectsOfCKEditor5Plugin(
 *     deriver = "Drupal\embed\Deriver\EmbedCKEditor5PluginDeriver",
 *     elements = {
 *       "<your-embed-tag>",
 *       "<your-embed-tag data-embed-button>",
 *     },
 *     conditions = {
 *       "filter" = "your_embed_filter_id",
 *     },
 *   ),
 * @endcode
 *
 * @see \Drupal\ckeditor5\Annotation\CKEditor5Plugin
 */
abstract class EmbedCKEditor5PluginBase extends CKEditor5PluginDefault implements ContainerFactoryPluginInterface {

  /**
   * The CSRF Token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DrupalEntity constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF Token generator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   */
  public function __construct(array $configuration, string $plugin_id, CKEditor5PluginDefinition $plugin_definition, CsrfTokenGenerator $csrf_token_generator, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->csrfTokenGenerator = $csrf_token_generator;
    $this->entityTypeManager = $entity_type_manager;
    if (!isset($plugin_definition['embed_type_id'])) {
      throw new InvalidPluginDefinitionException($plugin_id, sprintf('The %s plugin must define the embed_type_id property.', $plugin_id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('csrf_token'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons(): array {
    $buttons = [];

    if ($embed_buttons = $this->entityTypeManager->getStorage('embed_button')->loadByProperties(['type_id' => $this->pluginDefinition['embed_type_id']])) {
      foreach ($embed_buttons as $embed_button) {
        $buttons[$embed_button->id()] = $this->getButton($embed_button);
      }
    }

    return $buttons;
  }

  /**
   * Build the information about the specific button.
   *
   * @param \Drupal\embed\EmbedButtonInterface $embed_button
   *   The embed button.
   *
   * @return array
   *   The array for use with getButtons().
   */
  protected function getButton(EmbedButtonInterface $embed_button): array {
    $label = Html::escape($embed_button->label());

    return [
      'id' => $embed_button->id(),
      'name' => $label,
      'label' => $label,
      'image' => $embed_button->getIconUrl(),
    ];
  }

  /**
   * Get the embed preview route CSRF token.
   */
  public function getEmbedPreviewCsrfToken(): string {
    return $this->csrfTokenGenerator->get(EmbedController::PREVIEW_CSRF_TOKEN_NAME);
  }

}
