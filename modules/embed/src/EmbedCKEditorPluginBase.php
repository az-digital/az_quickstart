<?php

declare(strict_types=1);

namespace Drupal\embed;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Drupal\embed\Controller\EmbedController;
use Symfony\Component\DependencyInjection\ContainerInterface;

@trigger_error('EmbedCKEditorPluginBase is deprecated in embed:8.x-1.9 and is removed from embed:2.0.0. Use \Drupal\entity_embed\Plugin\CKEditor5Plugin\EmbedCKEditor5PluginBase instead. See https://www.drupal.org/node/3467748', E_USER_DEPRECATED);

/**
 * Provides a base class for embed CKEditor plugins.
 *
 * @deprecated in embed:8.x-1.9 and is removed from embed:2.0.0. Use
 *   \Drupal\entity_embed\Plugin\CKEditor5Plugin\EmbedCKEditor5PluginBase
 *   instead.
 *
 * @see https://www.drupal.org/node/3467748
 */
abstract class EmbedCKEditorPluginBase extends CKEditorPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * Constructs a Drupal\entity_embed\Plugin\CKEditorPlugin\DrupalEntity object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF token generator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, CsrfTokenGenerator $csrf_token_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->csrfTokenGenerator = $csrf_token_generator;
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
      $container->get('entity_type.manager'),
      $container->get('csrf_token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
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
  protected function getButton(EmbedButtonInterface $embed_button) {
    $info = [
      'id' => $embed_button->id(),
      'name' => Html::escape($embed_button->label()),
      'label' => Html::escape($embed_button->label()),
      'image' => $embed_button->getIconUrl(),
    ];
    $definition = $this->getPluginDefinition();
    if (!empty($definition['required_filter_plugin_id'])) {
      $info['required_filter_plugin_id'] = $definition['required_filter_plugin_id'];
    }
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'embed/embed',
    ];
  }

  /**
   * Get the embed preview route CSRF token.
   */
  public function getEmbedPreviewCsrfToken(): string {
    return $this->csrfTokenGenerator->get(EmbedController::PREVIEW_CSRF_TOKEN_NAME);
  }

}
