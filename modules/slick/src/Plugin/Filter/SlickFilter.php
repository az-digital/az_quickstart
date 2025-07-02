<?php

namespace Drupal\slick\Plugin\Filter;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\Plugin\Filter\BlazyFilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\slick\SlickDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter for a Slick.
 *
 * Best after Blazy, Align images, caption images.
 *
 * @Filter(
 *   id = "slick_filter",
 *   title = @Translation("Slick"),
 *   description = @Translation("Creates slideshow/ carousel with Slick shortcode."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "optionset" = "default",
 *     "media_switch" = "",
 *   },
 *   weight = 4
 * )
 */
class SlickFilter extends BlazyFilterBase {

  /**
   * {@inheritdoc}
   *
   * @see https://www.php.net/manual/en/reserved.keywords.php
   */
  protected static $namespace = 'slick';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'slide';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'slide';

  /**
   * {@inheritdoc}
   */
  protected static $captionId = 'caption';

  /**
   * {@inheritdoc}
   */
  protected static $shortcode = 'slide';

  /**
   * {@inheritdoc}
   */
  protected static $navId = 'thumb';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\slick\Form\SlickAdminInterface
   */
  protected $admin;

  /**
   * The slick formatter.
   *
   * @var \Drupal\slick\SlickFormatterInterface
   */
  protected $formatter;

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->admin = $container->get('slick.admin');
    $instance->manager = $container->get('slick.manager');

    // For consistent call against ecosystem shared methods:
    $instance->formatter = $container->get('slick.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'settings' => array_merge($this->pluginDefinition['settings'], SlickDefault::filterSettings()),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $this->result = $result = new FilterProcessResult($text);
    $this->langcode = $langcode;

    if (empty($text) || stristr($text, '[' . static::$namespace) === FALSE) {
      return $result;
    }

    $attachments = [];
    $settings = $this->buildSettings($text);
    $text = $this->shortcode($text, static::$namespace, static::$itemId);
    $dom = Html::load($text);
    $nodes = $this->validNodes($dom, [static::$namespace]);

    if (count($nodes) > 0) {
      foreach ($nodes as $node) {
        if ($output = $this->build($node, $settings)) {
          $this->render($node, $output);
        }
      }

      $attach = $this->attach($settings);
      $attachments = $this->manager->attach($attach);
    }

    // Attach Blazy component libraries.
    $result->setProcessedText(Html::serialize($dom))
      ->addAttachments($attachments);

    return $result;
  }

  /**
   * Build the slick.
   */
  private function build(&$object, array $settings): array {
    $dataset = $object->getAttribute('data');

    if (!empty($dataset) && mb_strpos($dataset, ":") !== FALSE) {
      $dataset = strip_tags($dataset);
      $object->setAttribute('data', '');
      return $this->withEntityShortcode($object, $settings, $dataset);
    }

    return $this->withDomShortcode($object, $settings);
  }

  /**
   * Build the slick using the node ID and field_name.
   */
  private function withEntityShortcode(\DOMElement $object, array $settings, $attribute): array {
    $list = $this->formatterSettings($settings, $attribute);

    if (!$list) {
      return [];
    }

    $blazies = $settings['blazies'];
    $count = $blazies->get('count');

    if ($count > 0 && $type = $blazies->get('field.type')) {
      $formatter = NULL;
      $handler = $blazies->get('field.handler');
      $settings['view_mode'] = ($settings['view_mode'] ?? '') ?: 'default';

      $build = ['#settings' => $settings];

      $this->prepareBuild($build, $object);
      $settings = $build['#settings'];
      $texts = ['text', 'text_long', 'text_with_summary'];

      // @todo refine for main stage, etc.
      if ($type == 'entity_reference'
        || $type == 'entity_reference_revisions') {
        if ($handler == 'default:media') {
          $formatter = 'slick_media';
        }
        else {
          // @todo refine for Paragraphs, etc.
          if ($type == 'entity_reference_revisions') {
            $formatter = 'slick_paragraphs_media';
          }
          else {
            $settings['vanilla'] = TRUE;
            if ($this->manager->moduleExists('slick_entityreference')) {
              $formatter = 'slick_entityreference';
            }
          }
        }
      }
      elseif ($type == 'image') {
        $formatter = 'slick_image';
      }
      elseif (in_array($type, ['file', 'svg_image_field'])) {
        $formatter = 'slick_file';
      }
      elseif (in_array($type, $texts)) {
        $formatter = 'slick_text';
      }

      if ($formatter) {
        return $list->view([
          'type' => $formatter,
          'settings' => $settings,
        ]);
      }
    }
    return [];
  }

  /**
   * Build the slick using the DOM lookups.
   */
  private function withDomShortcode($object, array $settings): array {
    $text = $this->getHtml($object);

    if (empty($text)) {
      return [];
    }

    $dom = Html::load($text);
    $nodes = $this->getNodes($dom, '//' . static::$itemId);

    if ($nodes->length == 0) {
      return [];
    }

    $blazies = $settings['blazies'];
    $settings['count'] = $count = $nodes->length;

    $blazies->set('count', $count)
      ->set('total', $count);

    $build = ['#settings' => $settings];

    $this->prepareBuild($build, $object);

    foreach ($nodes as $delta => $node) {
      if (!($node instanceof \DOMElement)) {
        continue;
      }

      $sets = $build['#settings'];
      $blazies = $sets['blazies']->reset($sets);

      $sets['delta'] = $delta;
      $blazies->set('delta', $delta);

      $thumb = $node->getAttribute('data-b-thumb');

      // @todo remove data-thumb for data-b-thumb at 3.x.
      if (!$thumb) {
        $thumb = $node->getAttribute('data-thumb');
      }

      if ($thumb) {
        $sets['thumbnail_uri'] = $thumb;
        $blazies->set('thumbnail.uri', $thumb);
      }

      $data = [
        '#delta' => $delta,
        '#item' => NULL,
        '#settings' => $sets,
      ];
      $element = $this->withDomElement($data, $node, $delta);

      if (empty($element[static::$itemId])) {
        $element[static::$itemId] = ['#markup' => $dom->saveHtml($node)];
      }

      $build['items'][] = $element;

      // Build individual slick thumbnail.
      if (!empty($sets['nav'])) {
        $this->withNavigation($build, $element, $delta);
      }
    }

    return $this->manager->build($build);
  }

  /**
   * Build the slide item.
   */
  private function withDomElement(array &$build, $node, $delta): array {
    $element = [];
    $text = $this->getHtml($node);

    if (empty($text)) {
      return $build;
    }

    $sets     = &$build['#settings'];
    $blazies  = $sets['blazies'];
    $dom      = Html::load($text);
    $xpath    = new \DOMXPath($dom);
    $children = $xpath->query("//iframe | //img");

    $this->buildItemAttributes($build, $node, $delta);

    if ($children->length > 0) {
      // Can only have the first found for the main slide stage.
      $child = $this->getValidNode($children);

      // Build item settings, image, and caption.
      $this->buildItemContent($build, $child, $delta);

      $uri = $sets['uri'] ?? '';
      $uri = $blazies->get('image.uri') ?: $uri;

      if ($uri) {
        $element = $this->toElement($blazies, $build);
      }
    }

    // At least provide the settings.
    if (!$element) {
      $element['#settings'] = $sets;
    }
    return $element;
  }

  /**
   * Prepares the slick.
   */
  private function prepareBuild(array &$build, $node): void {
    $sets    = &$build['#settings'];
    $blazies = $sets['blazies'];
    $slicks  = $sets['slicks'];
    $count   = $sets['count'] ?? 0;
    $count   = $blazies->get('count', 0) ?: $count;
    $options = [];

    if ($check = $node->getAttribute('options')) {
      $check = str_replace("'", '"', $check);
      if ($check) {
        $options = Json::decode($check);
      }
    }

    // Extract settings from attributes.
    $blazies->set('was.initialized', FALSE);
    $this->extractSettings($node, $sets);

    if (!isset($sets['nav'])) {
      $sets['nav'] = !empty($sets['optionset_thumbnail']) && $count > 1;
    }

    $nav = $sets['nav'];
    $grid = !empty($sets['style']) && !empty($sets['grid']);
    $sets['visible_items'] = $grid && empty($sets['visible_items']) ? 6 : $sets['visible_items'];

    $blazies->set('is.nav', $nav)
      ->set('is.grid', $grid);

    $slicks->set('is.nav', $nav);

    // Ensures disabling nav, also removing its optionset.
    if (!$nav) {
      $sets['optionset_thumbnail'] = '';
    }

    $build['#options'] = $options;
  }

  /**
   * Build the slick navigation.
   */
  private function withNavigation(array &$build, array $element, $delta): void {
    $sets    = &$element['#settings'];
    $item    = $this->manager->toHashtag($element, 'item', NULL);
    $caption = $sets['thumbnail_caption'] ?? NULL;
    $text    = [];

    if ($caption && $item && $check = $item->{$caption} ?? NULL) {
      $text = ['#markup' => Xss::filterAdmin($check)];
    }

    // Thumbnail usages: asNavFor pagers, dot, arrows, photobox thumbnails.
    $tn = $this->manager->getThumbnail($sets, $item, $text);
    $build[static::$navId]['items'][$delta] = $tn;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return file_get_contents(dirname(__FILE__) . "/FILTER_TIPS.txt");
    }

    return $this->t('<b>Slick</b>: Create a slideshow/ carousel: <br><ul><li><b>With self-closing using data entity, <code>data=ENTITY_TYPE:ID:FIELD_NAME:FIELD_IMAGE</code></b>:<br><code>[slick data="node:44:field_media" /]</code>. <code>FIELD_IMAGE</code> is optional for video poster, or hires, normally <code>field_media_image</code>.</li><li><b>With any HTML</b>: <br><code>[slick settings="{}" options="{}"]...[slide]...[/slide]...[/slick]</li></code></ul>');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $definition = [
      'settings' => $this->settings,
      'grid_form' => TRUE,
      'image_style_form' => TRUE,
      'media_switch_form' => TRUE,
      'background' => TRUE,
      'caches' => FALSE,
      'captions' => 'default',
      'multimedia' => TRUE,
      'style' => TRUE,
      'thumb_captions' => 'default',
      'thumb_positions' => TRUE,
      'thumbnail_style' => TRUE,
      'nav' => TRUE,
      'filter' => TRUE,
      'no_preload' => TRUE,
      'plugin_id' => $this->getPluginId(),
    ];

    $element = [];
    $this->admin->buildSettingsForm($element, $definition);

    if (isset($element['media_switch'])) {
      unset($element['media_switch']['#options']['content']);
    }

    if (isset($element['closing'])) {
      $element['closing']['#suffix'] = $this->t('Best after Blazy, Align / Caption images filters -- all are not required to function. Not tested against, nor dependent on, Shortcode module. Be sure to place Slick filter before any other Shortcode if installed.');
    }

    return $element;
  }

}
