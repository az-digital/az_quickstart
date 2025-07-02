<?php

namespace Drupal\webform\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\webform\Entity\Webform as WebformEntity;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;

/**
 * Provides a render element to display a webform.
 *
 * @RenderElement("webform")
 */
class Webform extends RenderElement {

  /**
   * Webform element default properties.
   *
   * @var array
   */
  protected static $defaultProperties = [
    '#webform' => NULL,
    '#default_data' => [],
    '#action' => NULL,
    '#sid' => NULL,
    '#information' => NULL,
  ];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderWebformElement'],
      ],
      '#lazy' => FALSE,
    ] + static::$defaultProperties;
  }

  /**
   * Webform element pre render callback.
   */
  public static function preRenderWebformElement($element) {
    // If #lazy, then return a lazy builder placeholder.
    if (!empty($element['#lazy'])) {
      $webform = $element['#webform'] ?? NULL;
      if ($webform instanceof WebformInterface) {
        $element['#webform'] = $webform->id();
      }
      $lazy_args = array_intersect_key($element, static::$defaultProperties);
      // In lazy mode, set the entity reference in the callback argument.
      // Otherwise, the source entity relationship is disconnected.
      $entity = $element['#entity'] ?? NULL;
      if ($entity instanceof EntityInterface) {
        $lazy_args['#entity_type'] = $entity->getEntityTypeId();
        $lazy_args['#entity_id'] = $entity->id();
      }
      $serialized_args = Json::encode($lazy_args);
      return [
        'lazy_builder' => [
          '#lazy_builder' => ['\Drupal\webform\Element\Webform::lazyBuilder', [$serialized_args]],
          '#create_placeholder' => TRUE,
        ],
      ];
    }

    $webform = ($element['#webform'] instanceof WebformInterface) ? $element['#webform'] : WebformEntity::load($element['#webform']);
    if (!$webform) {
      return $element;
    }

    if (!empty($element['#sid'])) {
      $webform_submission = WebformSubmission::load($element['#sid']);
      if ($webform_submission
        && $webform_submission->access('update')
        && $webform_submission->getWebform()->id() === $webform->id()) {
        $element['webform_build'] = \Drupal::service('entity.form_builder')
          ->getForm($webform_submission, 'edit');
      }
      elseif ($webform->getSetting('form_access_denied') !== WebformInterface::ACCESS_DENIED_DEFAULT) {
        // Set access denied message.
        $element['webform_access_denied'] = static::buildAccessDenied($webform);
      }
      else {
        static::addCacheableDependency($element, $webform);
      }
    }
    else {
      if ($webform->access('submission_create')) {
        $values = [];

        // Set data.
        $values['data'] = $element['#default_data'];

        // Set source entity type and id.
        if (!empty($element['#entity']) && $element['#entity'] instanceof EntityInterface) {
          $values['entity_type'] = $element['#entity']->getEntityTypeId();
          $values['entity_id'] = $element['#entity']->id();
        }
        elseif (!empty($element['#entity_type']) && !empty($element['#entity_id'])) {
          $values['entity_type'] = $element['#entity_type'];
          $values['entity_id'] = $element['#entity_id'];
        }

        // Build the webform.
        $element['webform_build'] = $webform->getSubmissionForm($values);

        // Add url.path to cache contexts.
        $meta = new CacheableMetadata();
        $meta->setCacheContexts(['url.path']);
        $renderer = \Drupal::service('renderer');
        $renderer->addCacheableDependency($element, $meta);
        static::addCacheableDependency($element, $webform);
      }
      elseif ($webform->getSetting('form_access_denied') !== WebformInterface::ACCESS_DENIED_DEFAULT) {
        // Set access denied message.
        $element['webform_access_denied'] = static::buildAccessDenied($webform);
      }
      else {
        static::addCacheableDependency($element, $webform);
      }
    }

    if (isset($element['webform_build'])) {
      // Set custom form submit action.
      if (!empty($element['#action'])) {
        $element['webform_build']['#action'] = $element['#action'];
      }
      // Hide submission information.
      if ($element['#information'] === FALSE
        && isset($element['webform_build']['information'])) {
        $element['webform_build']['information']['#access'] = FALSE;
      }
    }

    // Allow anonymous drafts to be restored.
    // @see \Drupal\webform\WebformSubmissionForm::buildForm
    if (\Drupal::currentUser()->isAnonymous()
      && $webform->getSetting('draft') === WebformInterface::DRAFT_ALL) {
      $element['#cache']['max-age'] = 0;
      // @todo Remove once bubbling of element's max-age to page cache is fixed.
      // @see https://www.drupal.org/project/webform/issues/3015760
      // @see https://www.drupal.org/project/drupal/issues/2352009
      if (\Drupal::moduleHandler()->moduleExists('page_cache')) {
        \Drupal::service(('page_cache_kill_switch'))->trigger();
      }
    }

    return $element;
  }

  /**
   * Build access denied message for a webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   A renderable array containing thea access denied message for a webform.
   */
  public static function buildAccessDenied(WebformInterface $webform) {
    /** @var \Drupal\webform\WebformTokenManagerInterface $webform_token_manager */
    $webform_token_manager = \Drupal::service('webform.token_manager');

    // Message.
    $config = \Drupal::configFactory()->get('webform.settings');
    $message = $webform->getSetting('form_access_denied_message')
      ?: $config->get('settings.default_form_access_denied_message');
    $message = $webform_token_manager->replace($message, $webform);

    // Attributes.
    $attributes = $webform->getSetting('form_access_denied_attributes');
    $attributes['class'][] = 'webform-access-denied';

    $build = [
      '#theme' => 'webform_access_denied',
      '#attributes' => $attributes,
      '#message' => WebformHtmlEditor::checkMarkup($message),
      '#webform' => $webform,
    ];

    return static::addCacheableDependency($build, $webform);
  }

  /**
   * Adds webform.settings and webform as cache dependencies to a render array.
   *
   * @param array &$elements
   *   The render array to update.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   A render array with webform.settings and webform as cache dependencies.
   */
  public static function addCacheableDependency(array &$elements, WebformInterface $webform) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    // Track if webform.settings is updated.
    $config = \Drupal::configFactory()->get('webform.settings');
    $renderer->addCacheableDependency($elements, $config);

    // Track if the webform is updated.
    $renderer->addCacheableDependency($elements, $webform);

    return $elements;
  }

  /**
   * #lazy_builder callback; renders a webform.
   *
   * @return array
   *   A renderable array representing the webform.
   */
  public static function lazyBuilder($serialized_element) {
    return [
      'webform' => [
        '#type' => 'webform',
        '#lazy' => FALSE,
      ] + Json::decode($serialized_element),
    ];
  }

}
