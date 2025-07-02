<?php

namespace Drupal\block_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Plugin implementation of the 'block_field' formatter.
 *
 * @FieldFormatter(
 *   id = "block_field",
 *   label = @Translation("Block field"),
 *   field_types = {
 *     "block_field"
 *   }
 * )
 */
class BlockFieldFormatter extends FormatterBase implements TrustedCallbackInterface {

  /**
   * The Drupal context repository.
   *
   * @var \Drupal\context\Entity\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a StringFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   The context repository service.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $contextHandler
   *   The ContextHandler for applying contexts to conditions properly.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack symfony instance.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Current Route Matcher.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ContextRepositoryInterface $contextRepository, ContextHandlerInterface $contextHandler, AccountProxyInterface $current_user, ModuleHandlerInterface $module_handler, RequestStack $request_stack, TitleResolverInterface $title_resolver, RouteMatchInterface $route_match) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->contextRepository = $contextRepository;
    $this->contextHandler = $contextHandler;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->requestStack = $request_stack;
    $this->titleResolver = $title_resolver;
    $this->routeMatch = $route_match;
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
      $container->get('context.repository'),
      $container->get('context.handler'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('request_stack'),
      $container->get('title_resolver'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\block_field\BlockFieldItemInterface $item */
      $block_instance = $item->getBlock();

      // Inject runtime contexts.
      if ($block_instance instanceof ContextAwarePluginInterface) {
        try {
          $contexts = $this->contextRepository->getRuntimeContexts($block_instance->getContextMapping());
          $this->contextHandler->applyContextMapping($block_instance, $contexts);
        }
        catch (ContextException $e) {
          continue;
        }
      }

      // Make sure the block exists and is accessible.
      if (!$block_instance) {
        continue;
      }

      $access = $block_instance->access($this->currentUser->getAccount(), TRUE);
      CacheableMetadata::createFromRenderArray($elements)
        ->addCacheableDependency($access)
        ->applyTo($elements);
      if (!$access->isAllowed()) {
        continue;
      }

      if ($block_instance instanceof TitleBlockPluginInterface) {
        $title = $this->titleResolver->getTitle($this->requestStack->getCurrentRequest(), $this->routeMatch->getRouteObject());
        if ($title) {
          $block_instance->setTitle($title);
        }
      }

      // See \Drupal\block\BlockViewBuilder::buildPreRenderableBlock
      // See template_preprocess_block()
      $base_id = $block_instance->getBaseId();
      $elements[$delta] = [
        '#theme' => 'block',
        '#attributes' => [],
        '#configuration' => $block_instance->getConfiguration(),
        '#plugin_id' => $block_instance->getPluginId(),
        '#base_plugin_id' => $base_id,
        '#derivative_plugin_id' => $block_instance->getDerivativeId(),
        '#id' => $block_instance->getMachineNameSuggestion(),
        '#pre_render' => [
          [$this, 'preRender'],
        ],
        '#block' => $block_instance,
      ];

      CacheableMetadata::createFromRenderArray($elements[$delta])
        ->addCacheableDependency($block_instance)
        ->applyTo($elements[$delta]);

      // If an alter hook wants to modify the block contents, it can append
      // another #pre_render hook.
      $this->moduleHandler->alter([
        'block_view',
        "block_view_{$base_id}"
      ], $elements[$delta], $block_instance);

      // Allow altering of cacheability metadata or setting #create_placeholder.
      $this->moduleHandler->alter([
        'block_build',
        "block_build_{$base_id}"
      ], $elements[$delta], $block_instance);

    }
    return $elements;
  }

  /**
   * The #pre_render callback for building a block.
   *
   * Renders the content using the provided block plugin, and then:
   * - if there is no content, aborts rendering, and makes sure the block won't
   *   be rendered.
   * - if there is content, moves the contextual links from the block content to
   *   the block itself.
   *
   * @see \Drupal\block\BlockViewBuilder::preRender
   */
  public function preRender($build) {
    $content = $build['#block']->build();
    // Remove the block entity from the render array, to ensure that blocks
    // can be rendered without the block config entity.
    unset($build['#block']);
    if ($content !== NULL && !Element::isEmpty($content)) {
      // Place the $content returned by the block plugin into a 'content' child
      // element, as a way to allow the plugin to have complete control of its
      // properties and rendering (for instance, its own #theme) without
      // conflicting with the properties used above, or alternate ones used by
      // alternate block rendering approaches in contrib (for instance, Panels).
      // However, the use of a child element is an implementation detail of this
      // particular block rendering approach. Semantically, the content returned
      // by the plugin "is the" block, and in particular, #attributes and
      // #contextual_links is information about the *entire* block. Therefore,
      // we must move these properties from $content and merge them into the
      // top-level element.
      foreach (['#attributes', '#contextual_links'] as $property) {
        if (isset($content[$property])) {
          if (empty($build[$property])) {
            $build[$property] = [];
          }
          $build[$property] += $content[$property];
          unset($content[$property]);
        }
      }
      $build['content'] = $content;
    }
    // Either the block's content is completely empty, or it consists only of
    // cacheability metadata.
    else {
      // Abort rendering: render as the empty string and ensure this block is
      // render cached, so we can avoid the work of having to repeatedly
      // determine whether the block is empty. For instance, modifying or adding
      // entities could cause the block to no longer be empty.
      $build = [
        '#markup' => '',
        '#cache' => $build['#cache'],
      ];
    }

    // If $content is not empty, then it contains cacheability metadata, and
    // we must merge it with the existing cacheability metadata. This allows
    // blocks to be empty, yet still bubble cacheability metadata, to indicate
    // why they are empty.
    if (!empty($content)) {
      CacheableMetadata::createFromRenderArray($build)
        ->merge(CacheableMetadata::createFromRenderArray($content))
        ->applyTo($build);
    }

    return $build;
  }

  public static function trustedCallbacks() {
    return ['preRender'];
  }
}
