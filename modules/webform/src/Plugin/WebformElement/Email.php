<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'email' element.
 *
 * @WebformElement(
 *   id = "email",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Email.php/class/Email",
 *   label = @Translation("Email"),
 *   description = @Translation("Provides a form element for entering an email address."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class Email extends TextBase {

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
    return [
      'input_hide' => FALSE,
    ] + parent::defineDefaultProperties()
      + $this->defineDefaultMultipleProperties();
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    if (empty($value)) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'link':
        return [
          '#type' => 'link',
          '#title' => $value,
          '#url' => $this->pathValidator->getUrlIfValid('mailto:' . $value),
        ];

      default:
        return parent::formatHtmlItem($element, $webform_submission, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'link';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'link' => $this->t('Link'),
    ];
  }

}
