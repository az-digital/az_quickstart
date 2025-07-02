<?php

namespace Drupal\webform_entity_print\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base deriver for webform entity print plugins.
 */
abstract class WebformEntityPrintWebformDeriverBase extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity print export type manager.
   *
   * @var \Drupal\entity_print\Plugin\ExportTypeManagerInterface
   */
  protected $exportTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $instance = new static();
    $instance->exportTypeManager = $container->get('plugin.manager.entity_print.export_type');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    return [];
  }

  /**
   * Get export type definitions.
   *
   * @return array
   *   An array of export type definitions.
   */
  protected function getDefinitions() {
    $definitions = $this->exportTypeManager->getDefinitions();

    // Remove unsupported export types.
    // Issue #2733781: Add Export to Word Support.
    // @see https://www.drupal.org/project/entity_print/issues/2733781
    unset($definitions['word_docx']);
    // Issue #2735559: Add Export to ePub.
    // @see https://www.drupal.org/project/entity_print/issues/2735559
    unset($definitions['epub']);

    return $definitions;
  }

}
