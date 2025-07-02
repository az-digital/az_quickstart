<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Apply Editor styles to configured elements.
 *
 * Replace HTML elements with elements and classes specified in the Styles menu
 * of the WYSIWYG editor.
 *
 * Available configuration keys:
 * - format: the text format to inspect for style options (optional,
 *   defaults to 'basic_html').
 * - rules: an array of keyed arrays, with the following keys:
 *   - xpath: an XPath expression for the elements to replace.
 *   - style: the label of the item in the Styles menu to use.
 *   - depth: the number of parent elements to remove (optional, defaults to 0).
 *
 * Example:
 *
 * @code
 * process:
 *   'body/value':
 *     -
 *       plugin: dom
 *       method: import
 *       source: 'body/0/value'
 *     -
 *       plugin: dom_apply_styles
 *       format: full_html
 *       rules:
 *         -
 *           xpath: '//b'
 *           style: Bold
 *         -
 *           xpath: '//span/i'
 *           style: Italic
 *           depth: 1
 *     -
 *       plugin: dom
 *       method: export
 * @endcode
 *
 * This will replace <b>...</b> with whatever style is labeled "Bold" in the
 * Full HTML text format, perhaps <strong class="foo">...</strong>.
 * It will also replace <span><i>...</i></span> with the style labeled "Italic"
 * in that text format, perhaps <em class="foo bar">...</em>.
 * You may get unexpected results if there is anything between the two opening
 * tags or between the two closing tags. That is, the code assumes that
 * '<span><i>' is closed with '</i></span>' exactly.
 *
 * @MigrateProcessPlugin(
 *   id = "dom_apply_styles"
 * )
 */
class DomApplyStyles extends DomProcessBase implements ContainerFactoryPluginInterface {

  protected ConfigFactory $configFactory;

  /**
   * Styles from the WYSIWYG editor.
   */
  protected array $styles = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config_factory) {
    $configuration += ['format' => 'basic_html'];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->setStyles($configuration['format']);
    $this->validateRules();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): \DOMDocument {
    $this->init($value, $destination_property);

    foreach ($this->configuration['rules'] as $rule) {
      $this->apply($rule);
    }

    return $this->document;
  }

  /**
   * Retrieve the list of styles based on configuration.
   *
   * The styles configuration is a string: styles are separated by "\r\n", and
   * each one has the format 'element(\.class)*|label'.
   * Convert this to an array with 'label' => 'element.class', and save as
   * $this->styles.
   *
   * @param string $format
   *   The text format from which to get configured styles.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function setStyles($format): void {
    if (empty($format) || !is_string($format)) {
      $message = 'The "format" option must be a non-empty string.';
      throw new InvalidPluginDefinitionException($this->getPluginId(), $message);
    }
    $editor_config = $this->configFactory->get("editor.editor.$format");
    if ($editor_config->get('editor') === 'ckeditor') {
      $editor_styles = $editor_config->get('settings.plugins.stylescombo.styles') ?? '';
      foreach (explode("\r\n", $editor_styles) as $rule) {
        if (preg_match('/(.*)\|(.*)/', $rule, $matches)) {
          $this->styles[$matches[2]] = $matches[1];
        }
      }
    }
    else if ($editor_config->get('editor') === 'ckeditor5') {
      $editor_styles = $editor_config->get('settings.plugins.ckeditor5_style.styles') ?? [];
      foreach ($editor_styles as $editor_style) {
        if (preg_match('/<(.*) class="(.*)">/', $editor_style['element'], $matches)) {
          $this->styles[$editor_style['label']] = $matches[1] . '.' . $matches[2];
        }
      }
    }
  }

  /**
   * Validate the configured rules.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function validateRules(): void {
    if (!array_key_exists('rules', $this->configuration) || !is_array($this->configuration['rules'])) {
      $message = 'The "rules" option must be an array.';
      throw new InvalidPluginDefinitionException($this->getPluginId(), $message);
    }
    foreach ($this->configuration['rules'] as $rule) {
      if (empty($rule['xpath']) || empty($rule['style'])) {
        $message = 'The "xpath" and "style" options are required for each rule.';
        throw new InvalidPluginDefinitionException($this->getPluginId(), $message);
      }
      if (empty($this->styles[$rule['style']])) {
        $message = sprintf('The style "%s" is not defined.', $rule['style']);
        throw new InvalidPluginDefinitionException($this->getPluginId(), $message);
      }
    }
  }

  /**
   * Apply a rule to the document.
   *
   * Search $this->document for elements matching 'xpath' and replace them with
   * the HTML elements and classes in $this->styles specified by 'style'.
   * If 'depth' is positive, then replace additional parent elements as well.
   *
   * @param string[] $rule
   *   An array with keys 'xpath', 'style', and (optional) 'depth'.
   */
  protected function apply(array $rule): void {
    // An entry in $this->styles has the format element(\.class)*: for example,
    // 'p' or 'a.button' or 'div.col-xs-6.col-md-4'.
    // @see setStyles()
    [$element, $classes] = explode('.', $this->styles[$rule['style']] . '.', 2);
    $classes = trim(str_replace('.', ' ', $classes));

    foreach ($this->xpath->query($rule['xpath']) as $node) {
      $new_node = $this->document->createElement($element);
      foreach ($node->childNodes as $child) {
        $new_node->appendChild($child->cloneNode(TRUE));
      }
      if ($classes) {
        $new_node->setAttribute('class', $classes);
      }
      $old_node = $node;
      if (!empty($rule['depth'])) {
        for ($i = 0; $i < $rule['depth']; $i++) {
          $old_node = $old_node->parentNode;
        }
      }
      $old_node->parentNode->replaceChild($new_node, $old_node);
    }
  }

}
