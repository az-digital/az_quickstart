<?php

namespace Drupal\media_library_form_element\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformMultiple;
use Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'media_library' element.
 *
 * @WebformElement(
 *   id = "media_library",
 *   api = "https://www.drupal.org/project/media_library_form_element",
 *   label = @Translation("Media library"),
 *   description = @Translation("Provides a form element for media library."),
 *   category = "Entity reference elements",
 *   dependencies = {
 *     "media_library_form_element",
 *   },
 *   states_wrapper = TRUE,
 * )
 */
class MediaLibrary extends WebformElementBase {

  use WebformEntityReferenceTrait;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      'multiple' => FALSE,
      'bundles' => [],
    ] + parent::defineDefaultProperties() + $this->defineDefaultMultipleProperties();

    unset($properties['prepopulate']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityRepository = $container->get('entity.repository');
    $instance->entityTypeRepository = $container->get('entity_type.repository');
    $instance->selectionManager = $container->get('plugin.manager.entity_reference_selection');
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['media_library'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Media library settings'),
    ];

    $form['media_library']['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Media types'),
      '#description' => $this->t('Select which media types are selectable.'),
      '#required' => TRUE,
      '#options' => array_map(function($bundle) {
        return $bundle['label'];
      }, $this->entityTypeBundleInfo->getBundleInfo('media')),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $element['#allowed_bundles'] = $this->getMediaTypes($element);

    if (!$this->getElementProperty($element, 'multiple')) {
      $element['#cardinality'] = 1;
    }
    elseif ($this->getElementProperty($element, 'multiple') === TRUE) {
      $element['#cardinality'] = WebformMultiple::CARDINALITY_UNLIMITED;
    }
    else {
      $element['#cardinality'] = $this->getElementProperty($element, 'multiple');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if (!empty($element['#default_value']) && is_array($element['#default_value'])) {
      $element['#default_value'] = implode(',', $element['#default_value']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleValues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleWrapper() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementValidateCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementValidateCallbacks($element, $webform_submission);

    $element['#element_validate'][] = [get_class($this), 'validateMediaLibrary'];
  }

  /**
   * Set the value as array.
   */
  public static function validateMediaLibrary(array &$element, FormStateInterface $form_state, array &$completed_form) {
    $value = $element['#value'];

    if (!empty($element['#multiple'])) {
      $value = explode(',', $element['#value']);
      $value = array_filter($value);
    }

    $form_state->setValueForElement($element, $value ?: NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    if ($this->isDisabled()) {
      return NULL;
    }

    /** @var \Drupal\media\MediaStorage $media_storage */
    $media_storage = $this->entityTypeManager->getStorage('media');
    $query = $media_storage
      ->getQuery()
      ->condition('bundle', array_filter($element['#bundles']), 'IN')
      ->range(0, 50);

    $result = $query->accessCheck(TRUE)->execute();

    if (!empty($result)) {
      return array_rand($result);
    }

    return NULL;
  }

  /**
   * Get applicable bundles.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   The enabled media bundles.
   */
  protected function getMediaTypes(array $element) {
    return !empty($element['#bundles']) ? array_filter($element['#bundles']) : ['image'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetType(array $element) {
    return 'media';
  }

}
