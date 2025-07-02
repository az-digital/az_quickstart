<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'link' element.
 *
 * @WebformElement(
 *   id = "webform_link",
 *   label = @Translation("Link"),
 *   category = @Translation("Composite elements"),
 *   description = @Translation("Provides a form element to display a link."),
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformLink extends WebformCompositeBase {

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->pathValidator = $container->get('path.validator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties();
    // Link does not have select menus.
    unset(
      $properties['select2'],
      $properties['chosed']
    );
    return $properties;
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    return [
      'link' => [
        '#type' => 'link',
        '#title' => $value['title'] ?: $value['url'],
        '#url' => $this->pathValidator->getUrlIfValid($value['url']),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    return [
      'link' => new FormattableMarkup('@title (@url)', ['@title' => $value['title'], '@url' => $value['url']]),
    ];
  }

}
