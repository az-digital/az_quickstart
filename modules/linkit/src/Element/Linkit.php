<?php

namespace Drupal\linkit\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Url;

/**
 * Provides a form element for linkit.
 *
 * @FormElement("linkit")
 */
class Linkit extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#size' => 60,
      '#process' => [
        [$class, 'processLinkitAutocomplete'],
        [$class, 'processGroup'],
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderLinkitElement'],
        [$class, 'preRenderGroup'],
      ],
      '#theme' => 'input__textfield',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return Textfield::valueCallback($element, $input, $form_state);
  }

  /**
   * Adds linkit custom autocomplete functionality to elements.
   *
   * Instead of using the core autocomplete, we use our own.
   *
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Render\Element\FormElement::processAutocomplete
   */
  public static function processLinkitAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {
    $url = NULL;
    $access = FALSE;

    if (!empty($element['#autocomplete_route_name'])) {
      $parameters = $element['#autocomplete_route_parameters'] ?? [];
      $url = Url::fromRoute($element['#autocomplete_route_name'], $parameters)->toString(TRUE);
      /** @var \Drupal\Core\Access\AccessManagerInterface $access_manager */
      $access_manager = \Drupal::service('access_manager');
      $access = $access_manager->checkNamedRoute($element['#autocomplete_route_name'], $parameters, \Drupal::currentUser(), TRUE);
    }

    if ($access) {
      $metadata = BubbleableMetadata::createFromRenderArray($element);
      if ($access->isAllowed()) {
        $element['#attributes']['class'][] = 'form-linkit-autocomplete';
        $metadata->addAttachments(['library' => ['linkit/linkit.autocomplete']]);
        // Provide a data attribute for the JavaScript behavior to bind to.
        $element['#attributes']['data-autocomplete-path'] = $url->getGeneratedUrl();
        $metadata = $metadata->merge($url);
      }
      $metadata
        ->merge(BubbleableMetadata::createFromObject($access))
        ->applyTo($element);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderLinkitElement($element) {
    return Textfield::preRenderTextfield($element);
  }

}
