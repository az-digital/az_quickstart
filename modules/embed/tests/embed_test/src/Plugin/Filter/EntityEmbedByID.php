<?php

namespace Drupal\embed_test\Plugin\Filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders a full node view from an embed code like node:NID.
 *
 * @Filter(
 *   id = "embed_test_node",
 *   title = @Translation("Test Node"),
 *   description = @Translation("Embeds nodes using node:NID embed codes."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class EntityEmbedByID extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a EntityEmbedByID object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
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
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    $matches = [];
    preg_match_all('/node:([0-9]+)/', $text, $matches);

    foreach ($matches[0] as $i => $search) {
      $replace = '';
      if ($node = $this->entityTypeManager->getStorage('node')->load($matches[1][$i])) {
        $build = $this->entityTypeManager->getViewBuilder('node')->view($node);
        $replace = $this->renderer->executeInRenderContext(new RenderContext(), function () use (&$build) {
          return $this->renderer->render($build);
        });
        $result = $result->merge(BubbleableMetadata::createFromRenderArray($build));
      }
      $text = str_replace($search, $replace, $text);
    }

    $result->setProcessedText($text);
    return $result;
  }

}
