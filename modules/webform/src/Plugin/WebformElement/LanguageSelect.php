<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'language_select' element.
 *
 * @WebformElement(
 *   id = "language_select",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!LanguageSelect.php/class/LanguageSelect",
 *   label = @Translation("Language select"),
 *   description = @Translation("Provides a form element for selecting a language."),
 *   hidden = TRUE,
 * )
 */
class LanguageSelect extends WebformElementBase {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties();
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
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $language = $this->languageManager->getLanguage($value);
    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'langcode':
        return $language->getId();

      case 'language':
        return $language->getName();

      case 'text':
      default:
        // Use `sprintf` instead of FormattableMarkup because we really just
        // want a basic string.
        return sprintf('%s (%s)', $language->getName(), $language->getId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'text';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'text' => $this->t('Text'),
      'langcode' => $this->t('Langcode'),
      'language' => $this->t('Language'),
    ];
  }

}
