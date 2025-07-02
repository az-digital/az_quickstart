<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformFormHelper;
use Drupal\webform\Utility\WebformXss;

/**
 * Provides a webform element for entering HTML using CodeMirror, TextFormat, or custom CKEditor.
 *
 * @FormElement("webform_html_editor")
 */
class WebformHtmlEditor extends FormElement implements TrustedCallbackInterface {

  /**
   * Default webform filter format.
   */
  const DEFAULT_FILTER_FORMAT = 'webform_default';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformHtmlEditor'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#format' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $element += ['#default_value' => ''];
    if ($input === FALSE) {
      return [
        'value' => $element['#default_value'],
      ];
    }
    else {
      // Get value from TextFormat element.
      if (isset($input['value']['value'])) {
        $input['value'] = $input['value']['value'];
      }
      return $input;
    }
  }

  /**
   * Prepares a #type 'webform_html_editor' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *
   * @return array
   *   The HTML Editor which can be a CodeMirror element, TextFormat, or
   *   Textarea which is transformed into a custom HTML Editor.
   */
  public static function processWebformHtmlEditor(array $element) {
    $element['#tree'] = TRUE;

    // Define value element.
    $element += ['value' => []];

    // Copy properties to value element.
    $properties = ['#title', '#required', '#attributes', '#default_value'];
    $element['value'] += array_intersect_key($element, array_combine($properties, $properties));

    // Hide title.
    $element['value']['#title_display'] = 'invisible';

    // Don't display inline form error messages.
    $element['#error_no_message'] = TRUE;

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformHtmlEditor']);

    // If HTML disabled and no #format is specified return simple CodeMirror
    // HTML editor.
    $disabled = \Drupal::config('webform.settings')->get('html_editor.disabled') ?: ($element['#format'] === FALSE);
    if ($disabled) {
      $element['value'] += [
        '#type' => 'webform_codemirror',
        '#mode' => 'html',
      ];
      return $element;
    }

    // If #format or 'webform.settings.html_editor.element_format' is defined return
    // a 'text_format' element.
    $format = $element['#format'] ?: \Drupal::config('webform.settings')->get('html_editor.element_format');
    if ($format && FilterFormat::load($format)) {
      $element['value'] += [
        '#type' => 'text_format',
        '#format' => $format,
        '#allowed_formats' => [$format],
        '#webform_html_editor' => TRUE,
        // Do not allow the text format value to be cleared when the text format
        // is hidden via #states. We must use a wrapper <div> because
        // The TextFormat element does not support #attributes.
        // @see \Drupal\webform\Plugin\WebformElement\TextFormat::preRenderFixTextFormatStates
        // @see \Drupal\filter\Element\TextFormat
        '#prefix' => '<div data-webform-states-no-clear>',
        '#suffix' => '</div>',
      ];
      if ($format === static::DEFAULT_FILTER_FORMAT) {
        $element['value']['#attributes']['class'][] = 'webform-html-editor-default-filter-format';
      }
      WebformElementHelper::fixStatesWrapper($element);
      $element['#attached']['library'][] = 'webform/webform.element.html_editor';
      return $element;
    }

    // Else use a textarea.
    $element['value'] += [
      '#type' => 'textarea',
    ];

    if (!empty($element['#states'])) {
      WebformFormHelper::processStates($element, '#wrapper_attributes');
    }

    return $element;
  }

  /**
   * Webform element validation handler for #type 'webform_html_editor'.
   */
  public static function validateWebformHtmlEditor(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value']['value'];
    if (is_array($value)) {
      // Get value from TextFormat element.
      $value = $value['value'];
    }
    else {
      $value = trim($value);
    }

    $element['#value'] = $value;
    $form_state->setValueForElement($element, $value);
  }

  /**
   * Get allowed content.
   *
   * @return string
   *   Allowed content (tags) for CKEditor.
   */
  public static function getAllowedContent() {
    $allowed_tags = \Drupal::config('webform.settings')->get('element.allowed_tags');
    switch ($allowed_tags) {
      case 'admin':
        $allowed_tags = Xss::getAdminTagList();
        break;

      case 'html':
        $allowed_tags = Xss::getHtmlTagList();
        break;

      default:
        $allowed_tags = preg_split('/ +/', $allowed_tags);
        break;
    }
    foreach ($allowed_tags as $index => $allowed_tag) {
      $allowed_tags[$index] .= '(*)[*]{*}';
    }
    return implode('; ', $allowed_tags);
  }

  /**
   * Get allowed tags.
   *
   * @return array
   *   Allowed tags.
   */
  public static function getAllowedTags() {
    $allowed_tags = \Drupal::config('webform.settings')->get('element.allowed_tags');
    switch ($allowed_tags) {
      case 'admin':
        return WebformXss::getAdminTagList();

      case 'html':
        return WebformXss::getHtmlTagList();

      default:
        return preg_split('/ +/', (string) $allowed_tags);
    }
  }

  /**
   * Runs HTML markup through (optional) text format.
   *
   * @param string $text
   *   The text to be filtered.
   * @param array $options
   *   HTML markup options.
   *
   * @return array
   *   Render array containing 'processed_text'.
   *
   * @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::getMessage
   */
  public static function checkMarkup($text, array $options = []) {
    $text = $text ?? '';

    $options += [
      'tidy' => \Drupal::config('webform.settings')->get('html_editor.tidy'),
    ];
    // Remove <p> tags around a single line of text, which creates minor
    // margin issues.
    if ($options['tidy']) {
      if (substr_count($text, '<p>') === 1 && preg_match('#^\s*<p>.*</p>\s*$#m', $text)) {
        $text = preg_replace('#^\s*<p>#', '', $text);
        $text = preg_replace('#</p>\s*$#', '', $text);
      }
    }

    $format = \Drupal::config('webform.settings')->get('html_editor.element_format');
    if ($format && $format !== static::DEFAULT_FILTER_FORMAT) {
      return [
        '#type' => 'processed_text',
        '#text' => $text,
        '#format' => $format,
      ];
    }
    else {
      return [
        '#theme' => 'webform_html_editor_markup',
        '#markup' => $text,
        '#allowed_tags' => static::getAllowedTags(),
      ];
    }
  }

  /**
   * Strip dis-allowed HTML tags from HTML text.
   *
   * @param string $text
   *   HTML text.
   *
   * @return string
   *   HTML text with dis-allowed HTML tags removed.
   */
  public static function stripTags($text) {
    return Xss::filter($text, static::getAllowedTags());
  }

  /* ************************************************************************ */
  // Text format and processed text callbacks.
  // @see \webform_element_info_alter()
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderTextFormat', 'preRenderProcessedText'];
  }

  /**
   * Process text format.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   radios or checkboxes element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete webform structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processTextFormat($element, FormStateInterface $form_state, &$complete_form) {
    if ($element['format']['format']['#default_value'] !== static::DEFAULT_FILTER_FORMAT) {
      unset(
        $element['format']['format']['#options'][static::DEFAULT_FILTER_FORMAT],
        $element['format']['guidelines'][static::DEFAULT_FILTER_FORMAT]
      );
    }
    return $element;
  }

  /**
   * Prepares a #type 'text_format'.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *
   * @return array
   *   The $element with prepared variables ready for theme_element().
   */
  public static function preRenderTextFormat(array $element) {
    // Remove guidelines and help from the 'webform_html_editor'.
    // @see \Drupal\webform\Element\WebformHtmlEditor::processWebformHtmlEditor
    if (!empty($element['#webform_html_editor'])) {
      unset(
        $element['format']['guidelines'],
        $element['format']['help']
      );
    }
    return $element;
  }

  /**
   * Pre-render callback: Renders a processed text element into #markup.
   *
   * @param array $element
   *   An element with the filtered text in '#markup'.
   *
   * @return array
   *   The passed-in element with the filtered text in '#markup'.
   */
  public static function preRenderProcessedText($element) {
    $format_id = $element['#format'] ?? NULL;
    if ($format_id === static::DEFAULT_FILTER_FORMAT) {
      // Determine if this is a CKEditor(4) test format tags to be processed.
      // (i.e, <h1>TEST</h1>)
      // @see \Drupal\ckeditor\Plugin\CKEditorPlugin\Internal::generateFormatTagsSetting
      // @see https://www.drupal.org/project/webform/issues/3331164
      $is_ckeditor_test = preg_match('#^<([a-z0-9]+)>TEST</\1>$#', $element['#markup'] ?? '');
      // Log issue, except for CKEditor(4) test format tags.
      if (!$is_ckeditor_test) {
        $message = "Disabled text format: %format. This text format can not be used outside of the Webform module's HTML editor.";
        \Drupal::logger('webform')->alert($message, ['%format' => $format_id]);
      }
      $element['#markup'] = '';
      return $element;
    }
    return $element;
  }

}
