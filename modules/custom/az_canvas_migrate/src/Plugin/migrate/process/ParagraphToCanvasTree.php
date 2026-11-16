<?php

namespace Drupal\az_canvas_migrate\Plugin\migrate\process;

use Drupal\az_canvas_migrate\CanvasComponentTreeBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts one paragraph's current revision into a Canvas component subtree.
 *
 * Settings with no Canvas equivalent are recorded as migrate messages.
 *
 * @code
 * tree:
 *   plugin: az_paragraph_to_canvas_tree
 *   source: id
 * @endcode
 */
#[MigrateProcess('az_paragraph_to_canvas_tree')]
class ParagraphToCanvasTree extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CanvasComponentTreeBuilder $treeBuilder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('az_canvas_migrate.component_tree_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $paragraph = $this->entityTypeManager->getStorage('paragraph')->load($value);
    if (!$paragraph instanceof ParagraphInterface) {
      throw new MigrateSkipRowException(sprintf('Paragraph %s does not exist.', $value));
    }
    $result = match ($paragraph->bundle()) {
      'az_rankings' => $this->treeBuilder->buildRankings($paragraph),
      default => throw new MigrateSkipRowException(sprintf('Paragraph type "%s" cannot be converted to Canvas components.', $paragraph->bundle())),
    };
    foreach ($result['warnings'] as $warning) {
      $migrate_executable->saveMessage($warning, MigrationInterface::MESSAGE_NOTICE);
    }
    return $result['tree'];
  }

}
