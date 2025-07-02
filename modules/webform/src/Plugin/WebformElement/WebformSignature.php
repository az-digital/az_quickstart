<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\webform\Element\WebformSignature as WebformSignatureElement;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Plugin\WebformElementFileDownloadAccessInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Provides a 'signature' element.
 *
 * @WebformElement(
 *   id = "webform_signature",
 *   label = @Translation("Signature"),
 *   description = @Translation("Provides a form element to collect electronic signatures from users. Signature support is provided by the <a href=""https://github.com/szimek/signature_pad"">Signature Pad</a> library."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class WebformSignature extends WebformElementBase implements WebformElementFileDownloadAccessInterface {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->fileSystem = $container->get('file_system');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      // General settings.
      'description' => $this->t('Sign above'),
      'readonly' => FALSE,
      'uri_scheme' => 'public',
    ] + parent::defineDefaultProperties();
    unset(
      $properties['format_items'],
      $properties['format_items_html'],
      $properties['format_items_text']
    );
    return $properties;
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    if (empty($element['#description'])) {
      $element['#description'] = $this->t('Sign above');
      $element['#description_display'] = 'after';
    }
    parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $format = $this->getItemFormat($element);

    switch ($format) {
      case 'image':
        if (empty($value)) {
          return '[' . $this->t('not signed') . ']';
        }

        $src = $this->getImageUrl($element, $webform_submission, $options);
        return $src ? [
          '#type' => 'html_tag',
          '#tag' => 'img',
          '#attributes' => [
            'src' => $src,
            'alt' => $this->t('Signature'),
            'class' => ['webform-signature-image'],
          ],
          '#attached' => ['library' => ['webform/webform.element.signature']],
        ] : '[' . $this->t('not valid') . ']';

      default:
        return parent::formatHtmlItem($element, $webform_submission, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'image':
      case 'status':
        $value = $this->getValue($element, $webform_submission, $options);
        return ($value) ? '[' . $this->t('signed') . ']' : '[' . $this->t('not signed') . ']';

      case 'url':
        return $this->getImageUrl($element, $webform_submission, $options);
    }

    return parent::formatTextItem($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'image';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return [
      'raw' => $this->t('Raw value'),
      'status' => $this->t('Status'),
      'url' => $this->t('URL'),
      'image' => $this->t('Image'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getExportDefaultOptions() {
    return [
      'signature_format' => 'status',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options) {
    parent::buildExportOptionsForm($form, $form_state, $export_options);
    if (isset($form['signature'])) {
      return;
    }

    $form['signature'] = [
      '#type' => 'details',
      '#title' => $this->t('Signature options'),
      '#open' => TRUE,
    ];
    $form['signature']['signature_format'] = [
      '#type' => 'radios',
      '#title' => $this->t('Signature format'),
      '#options' => [
        'image' => $this->t('Image, the signature\'s <a href=":href">Data URI</a>.', [':href' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs']),
        'status' => $this->t("Status, displays 'signed' or 'no signed'"),
      ],
      '#default_value' => $export_options['signature_format'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options) {
    $element['#format'] = ($export_options['signature_format'] === 'status') ? 'image' : 'raw';
    return [$this->formatText($element, $webform_submission, $export_options)];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return ['data:image/png;base64,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'];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Warn people about saving signatures when saving of results is disabled.
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getWebform();
    $form['signature'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Signature settings'),
      '#access' => TRUE,
    ];
    $scheme_options = static::getVisibleStreamWrappers();
    $uri_scheme = $this->getDefaultProperty('uri_scheme');
    $image_directory = $uri_scheme . '://webform/' . $webform->id() . '/{element_key}';
    $form['signature']['uri_scheme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Signature upload destination'),
      '#description' => $this->t('Select where the final Signatures should be stored. Both public and private storage store the signature using a secure hash as the file name. Public files should be adequate for most use cases. Private storage has more overhead than public files, but allows restricted access to files within this element.'),
      '#options' => $scheme_options,
      '#access' => TRUE,
      '#required' => TRUE,
    ];
    if ($webform->isResultsDisabled()) {
      $form['signature']['signature_message'] = [
        '#type' => 'webform_message',
        '#message_message' => '<strong>' . $this->t('Saving of results is disabled.') . '</strong> ' .
          $this->t('Signatures will still be saved to %directory.', ['%directory' => $image_directory]),
        '#message_type' => 'warning',
        '#access' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(array &$element, WebformSubmissionInterface $webform_submission) {
    $webform = $webform_submission->getWebform();
    $element_key = $element['#webform_key'];
    $sid = $webform_submission->id();

    // Delete signature image submission directory.
    $uri_scheme = $this->getElementProperty($element, 'uri_scheme');
    $image_base_directory = $uri_scheme . '://webform/' . $webform->id();
    $image_directory = "$image_base_directory/$element_key/$sid";
    if (file_exists($image_directory)) {
      $this->fileSystem->deleteRecursive($image_directory);
    }

    // Please node, the signature image (no results) directory is deleted when
    // the Webform is deleted.
    // @see \Drupal\webform\WebformEntityStorage::delete
  }

  /* ************************************************************************ */
  // Signature image helpers.
  /* ************************************************************************ */

  /**
   * Get a signature element's image URL.
   *
   * Signature image uses the public|private file system and the image name is
   * a secure hash and there is no risk of https://www.drupal.org/psa-2016-003.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return string
   *   A signature element's image URI.
   *
   * @see https://stackoverflow.com/questions/11511511/how-to-save-a-png-image-server-side-from-a-base64-data-string
   */
  protected function getImageUrl(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    if (!$value) {
      return '';
    }

    // Make sure existing signature values are valid.
    if (!WebformSignatureElement::isSignatureValid($value)) {
      return '';
    }

    $webform = $webform_submission->getWebform();
    $element_key = (isset($element['#webform_composite_key']))
      ? $element['#webform_composite_key']
      : $element['#webform_key'];
    $sid = $webform_submission->id();

    $uri_scheme = $this->getElementProperty($element, 'uri_scheme');
    $image_base_directory = $uri_scheme . '://webform/' . $webform->id();

    // Create signature image (no results) directory.
    $image_signature_directory = "$image_base_directory/$element_key";
    $this->fileSystem->prepareDirectory($image_signature_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $image_directory = $image_signature_directory;

    // Create signature image submission directory.
    if ($sid) {
      $image_submission_directory = "$image_base_directory/$element_key/$sid";
      $this->fileSystem->prepareDirectory($image_submission_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      $image_directory = $image_submission_directory;
    }

    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');

    // If a signature file was already created and shared using an
    // unsafe image hash, then return it.
    $unsafe_image_hash = Crypt::hmacBase64($value, Settings::getHashSalt());
    $unsafe_image_uri = "$image_directory/signature-$unsafe_image_hash.png";
    if (file_exists($unsafe_image_uri)) {
      return $file_url_generator->generateAbsoluteString($unsafe_image_uri);
    }

    $image_hash = Crypt::hmacBase64('webform-signature-' . $value, Settings::getHashSalt());
    $image_uri = "$image_directory/signature-$image_hash.png";

    if (!file_exists($image_uri)) {
      // Copy existing file.
      if ($sid && file_exists("$image_signature_directory/signature-$image_hash.png")) {
        $this->fileSystem->move(
          "$image_signature_directory/signature-$image_hash.png",
          "$image_directory/signature-$image_hash.png"
        );
      }
      else {
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $value));
        file_put_contents($image_uri, $data);
      }
    }

    return $file_url_generator->generateAbsoluteString($image_uri);
  }

  /**
   * Get visible stream wrappers.
   *
   * @return array
   *   An associative array of visible stream wrappers keyed by type.
   */
  public static function getVisibleStreamWrappers() {
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    return $stream_wrapper_manager->getNames(StreamWrapperInterface::WRITE_VISIBLE);
  }

  /**
   * {@inheritdoc}
   */
  public static function accessFileDownload($uri) {
    // Check if signature file.
    // URI patterns
    // - private://webform/[webform_id]/[element_key]/[sid]/signature-[hash].png.
    // - private://webform/[webform_id]/[element_key]/signature-[hash].png.
    // @see WebformSignature::getImageUrl().
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManager $stream_wrapper_manager */
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    $uri_target = $stream_wrapper_manager->getTarget($uri);
    if (!preg_match("/^webform\/(.*?)\/(.*?)\/(?:(.*?)\/)?signature-.*\.png$/", $uri_target, $matches)) {
      return NULL;
    }

    $webform_id = $matches[1];
    $element_key = $matches[2];
    $submission_id = (isset($matches[3])) ? $matches[3] : NULL;

    // Load webform and make sure it exist.
    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = Webform::load($webform_id);
    if (!$webform) {
      return NULL;
    }

    // Load element and make sure it is a signature.
    $webform_element = $webform->getElement($element_key);
    if ($webform_element['#type'] !== 'webform_signature') {
      return NULL;
    }

    $access = NULL;

    if ($submission_id) {
      // Check submission view access.
      $webform_submission = WebformSubmission::load($submission_id);
      if ($webform_submission && $webform_submission->access('view')) {
        $access = TRUE;
      }
    }
    else {
      // Check view any submission access.
      if ($webform->access('submission_view_any')) {
        $access = TRUE;
      }
    }

    if ($access === TRUE) {
      // Return file content headers.
      /** @var \Drupal\Core\File\FileSystemInterface $file_system */
      $file_system = \Drupal::service('file_system');
      $filename = $file_system->basename($uri);
      $filesize = filesize($file_system->realpath($uri));
      return [
        'Content-Type' => 'image/png',
        'Content-Length' => $filesize,
        'Cache-Control' => 'private',
        'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, (string) $filename),
      ];
    }
    elseif ($access === FALSE) {
      return -1;
    }
    else {
      return NULL;
    }
  }

}
