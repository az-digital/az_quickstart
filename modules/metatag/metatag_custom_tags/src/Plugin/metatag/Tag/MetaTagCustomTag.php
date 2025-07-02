<?php

declare(strict_types=1);

namespace Drupal\metatag_custom_tags\Plugin\metatag\Tag;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Random;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\metatag\MetatagSeparator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom configured meta tags will be available.
 *
 * The meta tag's values will be based upon this annotation.
 *
 * @MetatagTag(
 *   id = "metatag_custom_tag",
 *   deriver = "Drupal\metatag_custom_tags\Plugin\Derivative\MetaTagCustomTagDeriver",
 *   label = @Translation("Custom Tag"),
 *   description = @Translation("This plugin will be cloned from these settings for each custom tag."),
 *   name = "metatag_custom_tag",
 *   weight = 1,
 *   group = "metatag_custom_tags",
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class MetaTagCustomTag extends PluginBase {
  use MetatagSeparator;
  use StringTranslationTrait;

  /**
   * Machine name of the meta tag plugin.
   *
   * @var string
   */
  protected $id;

  /**
   * Official metatag name.
   *
   * @var string
   */
  protected $name;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $label;

  /**
   * A longer explanation of what the field is for.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $description;

  /**
   * The category this meta tag fits in.
   *
   * @var string
   */
  protected $group;

  /**
   * Type of the value being stored.
   *
   * @var string
   */
  protected $type;

  /**
   * True if URL must use HTTPS.
   *
   * @var bool
   */
  protected $secure;

  /**
   * True if more than one is allowed.
   *
   * @var bool
   */
  protected $multiple;

  /**
   * True if the tag should use a text area.
   *
   * @var bool
   */
  protected $long;

  /**
   * True if the tag should be trimmable.
   *
   * @var bool
   */
  protected $trimmable;

  /**
   * True if the URL value(s) must be absolute.
   *
   * @var bool
   */
  protected $absoluteUrl;

  /**
   * Retrieves the currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The value of the meta tag in this instance.
   *
   * @var string|array
   */
  protected $value;

  /**
   * The sort order for this meta tag.
   *
   * @var int
   */
  protected $weight;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The string this tag uses for the element itself.
   *
   * @var string
   */
  protected $htmlElement;

  /**
   * The attribute this tag uses for the name.
   *
   * @var string
   */
  protected $htmlNameAttribute;

  /**
   * The attribute this tag uses for the contents.
   *
   * @var string
   */
  protected $htmlValueAttribute;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Set the properties from the annotation.
    // @todo Should we have setProperty() methods for each of these?
    $this->id = $plugin_definition['id'];
    $this->name = $plugin_definition['name'];
    $this->label = $plugin_definition['label'];
    $this->description = $plugin_definition['description'] ?? '';
    $this->htmlElement = $plugin_definition['htmlElement'] ?? 'meta';
    $this->htmlNameAttribute = $plugin_definition['htmlNameAttribute'] ?? 'name';
    $this->htmlValueAttribute = $plugin_definition['htmlValueAttribute'] ?? 'content';
    $this->group = $plugin_definition['group'];
    $this->weight = $plugin_definition['weight'];
    $this->type = $plugin_definition['type'];
    $this->secure = !empty($plugin_definition['secure']);
    $this->multiple = !empty($plugin_definition['multiple']);
    $this->trimmable = !empty($plugin_definition['trimmable']);
    $this->long = !empty($plugin_definition['long']);
    $this->absoluteUrl = !empty($plugin_definition['absolute_url']);
    $this->request = \Drupal::request();

    // @todo Is there a DI-friendly way of doing this?
    $this->configFactory = \Drupal::service('config.factory');

    // Set an initial value.
    $this->value = '';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->setConfigFactory($container->get('config.factory'));
    return $instance;
  }

  /**
   * Sets ConfigFactoryInterface service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Config Factory service.
   */
  public function setConfigFactory(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Obtain the meta tag's internal ID.
   *
   * @return string
   *   This meta tag's internal ID.
   */
  public function id(): string {
    return $this->id;
  }

  /**
   * This meta tag's label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label.
   */
  public function label(): TranslatableMarkup|string {
    return $this->label;
  }

  /**
   * The meta tag's description.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   This meta tag's description.
   */
  public function description(): TranslatableMarkup|string {
    return $this->description;
  }

  /**
   * The meta tag's machine name.
   *
   * @return string
   *   This meta tag's machine name.
   */
  public function name(): string {
    return $this->name;
  }

  /**
   * The meta tag group this meta tag belongs to.
   *
   * @return string
   *   The meta tag's group name.
   */
  public function group(): string {
    return $this->group;
  }

  /**
   * This meta tag's form field's weight.
   *
   * @return int|float
   *   The form API weight for this. May be any number supported by Form API.
   */
  public function weight(): mixed {
    return $this->weight;
  }

  /**
   * Obtain this meta tag's type.
   *
   * @return string
   *   This meta tag's type.
   */
  public function type(): string {
    return $this->type;
  }

  /**
   * Determine whether this meta tag is an image tag.
   *
   * @return bool
   *   Whether this meta tag is an image.
   */
  public function isImage(): bool {
    return $this->type() == 'image';
  }

  /**
   * Whether or not this meta tag must output secure (HTTPS) URLs.
   *
   * @return bool
   *   Whether or not this meta tag must output secure (HTTPS) URLs.
   */
  public function secure(): bool {
    return $this->secure;
  }

  /**
   * Whether or not this meta tag must output secure (HTTPS) URLs.
   *
   * @return bool
   *   Whether or not this meta tag must output secure (HTTPS) URLs.
   */
  public function isSecure(): bool {
    return (bool) $this->secure;
  }

  /**
   * Whether or not this meta tag supports multiple values.
   *
   * @return bool
   *   Whether or not this meta tag supports multiple values.
   */
  public function multiple(): bool {
    return $this->multiple;
  }

  /**
   * Whether or not this meta tag supports multiple values.
   *
   * @return bool
   *   Whether or not this meta tag supports multiple values.
   */
  public function isMultiple(): bool {
    return (bool) $this->multiple;
  }

  /**
   * Whether or not this meta tag should use a text area.
   *
   * @return bool
   *   Whether or not this meta tag should use a text area.
   */
  public function isLong(): bool {
    return $this->long;
  }

  /**
   * Whether or not this meta tag stores a URL or URI value.
   *
   * @return bool
   *   Whether or not this meta tag should contain a URL or URI value.
   */
  public function isUrl(): bool {
    // Secure URLs are URLs.
    if ($this->isSecure()) {
      return TRUE;
    }
    // Absolute URLs are URLs.
    if ($this->requiresAbsoluteUrl()) {
      return TRUE;
    }
    // URIs are URL-adjacent.
    if ($this->type == 'uri') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get the HTML attribute used to store this meta tag's value.
   *
   * @return string
   *   The HTML attribute used to store this meta tag's value.
   */
  public function getHtmlValueAttribute(): string {
    return $this->htmlValueAttribute;
  }

  /**
   * Whether or not this meta tag must output required absolute URLs.
   *
   * @return bool
   *   Whether or not this meta tag must output required absolute URLs.
   */
  public function requiresAbsoluteUrl(): bool {
    return $this->absoluteUrl;
  }

  /**
   * Whether or not this meta tag is active.
   *
   * @return bool
   *   Whether this meta tag has been enabled.
   */
  public function isActive(): bool {
    return TRUE;
  }

  /**
   * Generate a form element for this meta tag.
   *
   * @param array $element
   *   The existing form element to attach to.
   *
   * @return array
   *   The completed form element.
   */
  public function form(array $element = []): array {
    $form = [
      '#type' => $this->isLong() ? 'textarea' : 'textfield',
      '#title' => $this->label(),
      '#default_value' => $this->value(),
      '#maxlength' => 1024,
      '#required' => $element['#required'] ?? FALSE,
      '#description' => $this->description(),
      '#element_validate' => [[get_class($this), 'validateTag']],
    ];

    // Optional handling for items that allow multiple values.
    $separator = $this->getSeparator();
    if (!empty($this->multiple)) {
      $form['#description'] .= ' ' . $this->t('Multiple values may be used, separated by `:delimiter`. Note: Tokens that return multiple values will be handled automatically.', [':delimiter' => $separator]);
    }

    // Optional handling for images.
    if ((!empty($this->type())) && ($this->type() === 'image')) {
      $form['#description'] .= ' ' . $this->t('This will be able to extract the URL from an image field if the field is configured properly.');
    }

    if (!empty($this->absolute_url)) {
      $form['#description'] .= ' ' . $this->t('Any relative or protocol-relative URLs will be converted to absolute URLs.');
    }

    // Optional handling for secure paths.
    if (!empty($this->secure)) {
      $form['#description'] .= ' ' . $this->t('Any URLs which start with "http://" will be converted to "https://".');
    }

    $settings = \Drupal::config('metatag.settings');
    $trimlengths = $settings->get('tag_trim_maxlength') ?? [];
    if (!empty($trimlengths['metatag_maxlength_' . $this->id])) {
      $maxlength = intval($trimlengths['metatag_maxlength_' . $this->id]);
      if (is_numeric($maxlength) && $maxlength > 0) {
        $form['#description'] .= ' ' . $this->t('This will be truncated to a maximum of %max characters after any tokens are processed.', ['%max' => $maxlength]);

        // Optional support for the Maxlength module.
        if (\Drupal::moduleHandler()->moduleExists('maxlength')) {
          if ($settings->get('use_maxlength') ?? TRUE) {
            $form['#attributes']['class'][] = 'maxlength';
            $form['#attached']['library'][] = 'maxlength/maxlength';
            $form['#maxlength_js'] = TRUE;
            $form['#attributes']['data-maxlength'] = $maxlength;
          }
        }
      }
    }

    return $form;
  }

  /**
   * Obtain the current meta tag's raw value.
   *
   * @return string|array
   *   The current raw meta tag value.
   */
  public function value(): string|array {
    return $this->value;
  }

  /**
   * Assign the current meta tag a value.
   *
   * @param mixed $value
   *   The value to assign this meta tag.
   */
  public function setValue($value): void {
    // If the argument is an array then store it as-is. If the argument is
    // anything else, convert it to a string.
    if (is_array($value)) {
      $this->value = $value;
    }
    else {
      $this->value = (string) $value;
    }
  }

  /**
   * Make the string presentable.
   *
   * This removes whitespace from either side of the string, and removes extra
   * whitespace inside the string so that it only contains one single space,
   * all line breaks and tabs are replaced by spaces.
   *
   * @param string $value
   *   The raw string to process.
   *
   * @return string
   *   The meta tag value after processing.
   */
  protected function tidy($value): string {
    if (is_null($value) || $value == '') {
      return '';
    }

    $value = str_replace(["\r\n", "\n", "\r", "\t"], ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);
    return trim($value);
  }

  /**
   * Generate the HTML tag output for a meta tag.
   *
   * @return array
   *   A render array.
   */
  public function output(): array {
    // If there is no value, just return either an empty array or empty string.
    if (is_null($this->value) || $this->value == '') {
      return [];
    }

    // Get configuration.
    $separator = $this->getSeparator();

    // If this contains embedded image tags, extract the image URLs.
    if ($this->type() === 'image') {
      $value = $this->parseImageUrl($this->value);
    }
    else {
      $value = PlainTextOutput::renderFromHtml($this->value);
    }

    $values = $this->multiple() ? explode($separator, $value) : [$value];
    $elements = [];
    foreach ($values as $value) {
      $value = $this->tidy($value);
      if ($value != '' && $this->requiresAbsoluteUrl()) {
        // Relative URL.
        if (parse_url($value, PHP_URL_HOST) == NULL) {
          $value = $this->request->getSchemeAndHttpHost() . $value;
        }
        // Protocol-relative URL.
        elseif (substr($value, 0, 2) === '//') {
          $value = $this->request->getScheme() . ':' . $value;
        }
      }

      // If tag must be secure, convert all http:// to https://.
      if ($this->secure() && strpos($value, 'http://') !== FALSE) {
        $value = str_replace('http://', 'https://', $value);
      }

      $value = $this->trimValue($value);

      $elements[] = [
        '#tag' => $this->htmlElement,
        '#attributes' => [
          $this->htmlNameAttribute => $this->name,
          $this->htmlValueAttribute => $value,
        ],
      ];
    }

    return $this->multiple() ? $elements : reset($elements);
  }

  /**
   * Validates the metatag data.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateTag(array &$element, FormStateInterface $form_state): void {
    // @todo If there is some common validation, put it here. Otherwise, make
    // it abstract?
  }

  /**
   * Extract any image URLs that might be found in a meta tag.
   *
   * @return string
   *   A comma separated list of any image URLs found in the meta tag's value,
   *   or the original string if no images were identified.
   */
  protected function parseImageUrl($value): string {
    global $base_root;

    // Skip all logic if the string is empty. Unlike other scenarios, the logic
    // in this method is predicated on the value being a legitimate string, so
    // it's ok to skip all possible "empty" values, including the number 0, etc.
    if (empty($value)) {
      return '';
    }

    // If image tag src is relative (starts with /), convert to an absolute
    // link; ignore protocol-relative URLs.
    $image_tag = FALSE;
    if (strpos($value, '<img src="/') !== FALSE && strpos($value, '<img src="//') === FALSE) {
      $value = str_replace('<img src="/', '<img src="' . $base_root . '/', $value);
      $image_tag = TRUE;
    }

    if ($this->multiple()) {
      // Split the string into an array, remove empty items.
      if ($image_tag) {
        preg_match_all('%\s*(|,\s*)(<\s*img\s+[^>]+>)%m', $value, $matches);
        $values = array_filter($matches[2] ?? []);
      }
      else {
        $values = array_filter(explode($this->getSeparator(), $value));
      }
    }
    else {
      $values = [$value];
    }

    // Check through the value(s) to see if there are any image tags.
    foreach ($values as $key => $val) {
      $matches = [];
      preg_match('/src="([^"]*)"/', $val, $matches);
      if (!empty($matches[1])) {
        $values[$key] = $matches[1];
      }

      // If an image wasn't found then remove any other HTML tags in the string.
      else {
        $values[$key] = PlainTextOutput::renderFromHtml($val);
      }
    }

    // Make sure there aren't any blank items in the array.
    $values = array_filter($values);

    // Convert the array back into a delimited string before sending it back.
    return implode($this->getSeparator(), $values);
  }

  /**
   * Trims a value if it is trimmable.
   *
   * This method uses metatag settings and the MetatagTrimmer service.
   *
   * @param string $value
   *   The string value to trim.
   *
   * @return string
   *   The trimmed string value.
   */
  protected function trimValue($value): string {
    if (TRUE === $this->trimmable) {
      $settings = \Drupal::config('metatag.settings');
      $trimMethod = $settings->get('tag_trim_method');
      $trimMaxlengthArray = $settings->get('tag_trim_maxlength');
      if (empty($trimMethod) || empty($trimMaxlengthArray)) {
        return $value;
      }
      $currentMaxValue = 0;
      foreach ($trimMaxlengthArray as $metaTagName => $maxValue) {
        if ($metaTagName == 'metatag_maxlength_' . $this->id) {
          $currentMaxValue = $maxValue;
        }
      }
      $trimmerService = \Drupal::service('metatag.trimmer');
      $value = $trimmerService->trimByMethod($value, $currentMaxValue, $trimMethod);
    }
    return $value;
  }

  /**
   * The xpath string which identifies this meta tag on a form.
   *
   * To skip testing the form field exists, return an empty array.
   *
   * @return string
   *   An xpath-formatted string for matching a field on the form.
   */
  public function getTestFormXpath(): array {
    // "Long" values use a text area on the form, so handle them automatically.
    if ($this->isLong()) {
      return [
        // @todo This should work but it results in the following error:
        // DOMXPath::query(): Invalid predicate.
        // "//textarea[@name='{$this->id}'",
      ];
    }
    // Default to a single text input field.
    else {
      return ["//input[@name='{$this->id}' and @type='text']"];
    }
  }

  /**
   * Generate a random value for testing purposes.
   *
   * As a reasonable default, this will generating two words of 8 characters
   * each with simple machine name -style strings; image meta tags will generate
   * an absolute URL for an image.
   *
   * @return array
   *   An array containing a normal string.
   */
  public function getTestFormData(): array {
    $random = new Random();

    // Provide a default value.
    if ($this->isImage()) {
      // @todo Add proper validation of image meta values.
      return [
        $this->id => 'https://www.example.com/images/' . $random->word(6) . '-' . $random->word(6) . '.png',
      ];
    }
    // Absolute URLs that are specifically secure.
    elseif ($this->isSecure()) {
      return [
        $this->id => 'https://www.example.com/' . $random->word(6) . '-' . $random->word(6) . '.html',
      ];
    }
    // Absolute URLs that are not necessarily secure.
    elseif ($this->requiresAbsoluteUrl()) {
      return [
        $this->id => 'http://www.example.com/' . $random->word(6) . '-' . $random->word(6) . '.html',
      ];
    }
    // Relative URLs.
    elseif ($this->isUrl()) {
      return [
        $this->id => '/' . $random->word(6) . '/' . $random->word(6) . '.html',
      ];
    }
    else {
      return [
        // Use three alphanumeric strings joined with spaces.
        $this->id => $random->word(6) . ' ' . $random->word(6) . ' ' . $random->word(6),
      ];
    }
  }

  /**
   * The xpath string which identifies this meta tag presence on the page.
   *
   * @return array
   *   A list of xpath-formatted string(s) for matching a field on the page.
   */
  public function getTestOutputExistsXpath(): array {
    return ["//" . $this->htmlElement . "[@" . $this->htmlNameAttribute . "='{$this->name}']"];
  }

  /**
   * The xpath string which identifies this meta tag's output on the page.
   *
   * @param array $values
   *   The field names and values that were submitted.
   *
   * @return array
   *   A list of xpath-formatted string(s) for matching a field on the page.
   */
  public function getTestOutputValuesXpath(array $values): array {
    $xpath_strings = [];
    foreach ($values as $value) {
      $xpath_strings[] = "//" . $this->htmlElement . "[@" . $this->htmlNameAttribute . "='{$this->name}' and @" . $this->htmlValueAttribute . "='{$value}']";
    }
    return $xpath_strings;
  }

}
