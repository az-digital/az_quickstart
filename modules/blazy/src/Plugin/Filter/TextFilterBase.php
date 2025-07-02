<?php

namespace Drupal\blazy\Plugin\Filter;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Render\FilteredMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base text or imageless filter utilities.
 */
abstract class TextFilterBase extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Defines the module namespace.
   *
   * @var string
   * @see https://www.php.net/manual/en/reserved.keywords.php
   */
  protected static $namespace = 'blazy';

  /**
   * The item identifier for content: content, slide, box, etc.
   *
   * @var string
   */
  protected static $itemId = 'slide';

  /**
   * The item identifier for captions: .blazy__caption, .slide__caption, etc.
   *
   * @var string
   */
  protected static $itemPrefix = 'slide';

  /**
   * {@inheritdoc}
   */
  protected static $captionId = 'caption';

  /**
   * The shortcode item identifier for grid, or slide, etc.: [item] or [slide].
   *
   * @var string
   */
  protected static $shortcode = 'slide';

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Filter manager.
   *
   * @var \Drupal\filter\FilterPluginManager
   */
  protected $filterManager;

  /**
   * Deprecated in blazy:2.17, removed from blazy:3.0.0. Use self::formatter.
   *
   * @var \Drupal\blazy\BlazyFormatterInterface
   *
   * @todo remove for $formatter to get consistent with sub-modules.
   */
  protected $blazyManager;

  /**
   * The blazy formatter.
   *
   * @var \Drupal\blazy\BlazyFormatterInterface
   */
  protected $formatter;

  /**
   * The sub-modules manager service.
   *
   * @var \Drupal\blazy\BlazyFormatterInterface
   */
  protected $manager;

  /**
   * The sub-modules admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface
   */
  protected $admin;

  /**
   * The filter HTML plugin.
   *
   * @var \Drupal\filter\Plugin\Filter\FilterHtml|null
   */
  protected $htmlFilter;

  /**
   * The langcode.
   *
   * @var string
   */
  protected $langcode;

  /**
   * The result.
   *
   * @var \Drupal\filter\FilterProcessResult|null
   */
  protected $result;

  /**
   * The excluded settings to fetch from attributes.
   *
   * @var array
   */
  protected $excludedSettings = ['filter_tags'];

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    $instance->root = $container->getParameter('app.root');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->filterManager = $container->get('plugin.manager.filter');
    $instance->admin = $container->get('blazy.admin.formatter');

    // For consistent call against ecosystem shared methods, Blazy has straight
    // inheritance, sub-modules deviate:
    $instance->manager = $instance->formatter = $container->get('blazy.formatter');

    // @todo remove for consistent call against ecosystem shared methods:
    $instance->blazyManager = $instance->manager;

    return $instance;
  }

  /**
   * Returns settings for attachments.
   */
  protected function attach(array $settings = []): array {
    $all = ['blazy' => TRUE, 'filter' => TRUE, 'ratio' => TRUE] + $settings;
    $all['media_switch'] = $switch = $settings['media_switch'] ?? '';

    if (!empty($settings[$switch])) {
      $all[$switch] = $settings[$switch];
    }

    return $all;
  }

  /**
   * Extracts setting from attributes.
   */
  protected function extractSettings(\DOMElement $node, array &$settings): void {
    $blazies = $settings['blazies'];

    // Ensures these settings are re-checked.
    $blazies->set('was.initialized', FALSE);

    if ($check = $node->getAttribute('settings')) {
      $check = str_replace("'", '"', $check);
      $check = Json::decode($check);
      if ($check) {
        $settings = array_merge($settings, $check);
      }
    }

    if ($nav = $node->getAttribute('nav')) {
      $settings['nav'] = $nav == 'false' ? FALSE : TRUE;
    }

    // Merge all defined attributes into settings for convenient.
    $defaults = $this->defaultConfiguration()['settings'] ?? [];
    if ($defaults) {
      foreach ($defaults as $key => $value) {
        if (in_array($key, $this->excludedSettings)) {
          continue;
        }

        $type = gettype($value);

        if ($node->hasAttribute($key)) {
          $node_value = $node->getAttribute($key);
          settype($node_value, $type);
          $settings[$key] = $node_value;
        }
      }
    }

    if (isset($settings['count'])) {
      $blazies->set('count', (int) $settings['count']);
    }

    AttributeParser::toGrid($node, $settings);
  }

  /**
   * Return sanitized caption, stolen from Filter caption.
   */
  protected function filterHtml($text): string {
    // Read the data-caption attribute's value, then delete it.
    $caption = Html::escape($text);

    // Sanitize caption: decode HTML encoding, limit allowed HTML tags; only
    // allow inline tags that are allowed by default, plus <br>.
    $caption = Html::decodeEntities($caption);
    $filtered_caption = $this->htmlFilter->process($caption, $this->langcode);

    if (isset($this->result)) {
      $this->result->addCacheableDependency($filtered_caption);
    }

    return FilteredMarkup::create($filtered_caption->getProcessedText());
  }

  /**
   * Returns the inner HTML of the DOMElement node.
   *
   * See https://www.php.net/manual/en/class.domelement.php#101243
   */
  protected function getHtml($node): ?string {
    $text = '';
    foreach ($node->childNodes as $child) {
      if ($child instanceof \DOMElement) {
        $text .= $child->ownerDocument->saveXML($child);
      }
    }
    return $text;
  }

  /**
   * Returns DOMElement nodes expected to be grid, or slide items.
   */
  protected function getNodes(\DOMDocument $dom, $tag = '//grid') {
    $xpath = new \DOMXPath($dom);

    return $xpath->query($tag);
  }

  /**
   * Returns a valid node, excluding blur/ bg images.
   */
  protected function getValidNode($children) {
    $child = $children->item(0);

    // @todo remove all these for b-filter after another check.
    $class   = $child->getAttribute('class');
    $is_blur = $class && strpos($class, 'b-blur') !== FALSE;
    $is_bg   = $class && strpos($class, 'b-bg') !== FALSE;

    if ($is_blur && !$is_bg) {
      $child = $children->item(1) ?: $child;
    }

    // With a dedicated b-filter, this should eliminate guess works above.
    foreach ($children as $node) {
      $class = $node->getAttribute('class');
      if (strpos($class, 'b-filter') !== FALSE) {
        $child = $node;
        break;
      }
    }
    return $child;
  }

  /**
   * Return common definitions.
   */
  protected function getPluginScopes(): array {
    return [
      'caches'    => FALSE,
      'filter'    => TRUE,
      'plugin_id' => $this->getPluginId(),
    ];
  }

  /**
   * Initialize the settings.
   */
  protected function init(array &$settings, $text): void {
    if (!isset($this->htmlFilter)) {
      $this->htmlFilter = $this->filterManager->createInstance('filter_html', [
        'settings' => [
          'allowed_html' => '<a href hreflang target rel> <em> <strong> <b> <i> <cite> <code> <br>',
          'filter_html_help' => FALSE,
          'filter_html_nofollow' => FALSE,
        ],
      ]);
    }
  }

  /**
   * Alias for Shortcode::parse().
   */
  protected function shortcode($text, $container = 'blazy', $item = 'item'): string {
    return Shortcode::parse($text, $container, $item);
  }

  /**
   * Prepares the settings.
   */
  protected function preSettings(array &$settings, $text): void {
    // Do nothing.
  }

  /**
   * Modifies the settings.
   */
  protected function postSettings(array &$settings): void {
    // Do nothing.
  }

  /**
   * Removes nodes.
   */
  protected function removeNodes(&$nodes): void {
    foreach ($nodes as $node) {
      if ($node->parentNode) {
        $node->parentNode->removeChild($node);
      }
    }
  }

  /**
   * Render the output.
   */
  protected function render(\DOMElement $node, array $output): void {
    $dom = $node->ownerDocument;
    $altered_html = $this->manager->renderer()->render($output);

    // Load the altered HTML into a new DOMDocument, retrieve element.
    $updated_nodes = Html::load($altered_html)->getElementsByTagName('body')
      ->item(0)
      ->childNodes;

    foreach ($updated_nodes as $updated_node) {
      // Import the updated from the new DOMDocument into the original
      // one, importing also the child nodes of the updated node.
      $updated_node = $dom->importNode($updated_node, TRUE);
      $node->parentNode->insertBefore($updated_node, $node);
    }

    // Finally, remove the original blazy node.
    if ($node->parentNode) {
      $node->parentNode->removeChild($node);
    }
  }

  /**
   * Return valid nodes based on the allowed tags.
   */
  protected function validNodes(\DOMDocument $dom, array $allowed_tags = [], $exclude = ''): array {
    $valid_nodes = [];
    foreach ($allowed_tags as $allowed_tag) {
      $nodes = $dom->getElementsByTagName($allowed_tag);
      if (property_exists($nodes, 'length') && $nodes->length > 0) {
        foreach ($nodes as $node) {
          if ($exclude && $node->hasAttribute($exclude)) {
            continue;
          }

          $valid_nodes[] = $node;
        }
      }
    }
    return $valid_nodes;
  }

}
