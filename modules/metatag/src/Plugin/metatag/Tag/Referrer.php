<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The basic "Referrer policy" meta tag.
 *
 * Note that this meta tag serves the same purpose as the HTTP header
 * "Referrer-Policy", so both are not needed.
 *
 * @MetatagTag(
 *   id = "referrer",
 *   label = @Translation("Referrer policy"),
 *   description = @Translation("Indicate to search engines and other page scrapers whether or not links should be followed. See <a href='https://w3c.github.io/webappsec/specs/referrer-policy/'>the W3C specifications</a> for further details. Note: this serves the same purpose as the HTTP header by the same name."),
 *   name = "referrer",
 *   group = "advanced",
 *   weight = 5,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Referrer extends MetaNameBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []): array {
    $form = [
      '#type' => 'select',
      '#title' => $this->label(),
      '#description' => $this->description(),
      '#options' => $this->formValues(),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#default_value' => $this->value(),
      '#required' => $element['#required'] ?? FALSE,
      '#element_validate' => [[get_class($this), 'validateTag']],
    ];

    return $form;
  }

  /**
   * The list of select values.
   *
   * @return array
   *   A list of values available for this select tag.
   */
  protected function formValues(): array {
    return [
      'no-referrer' => $this->t('No Referrer'),
      'no-referrer-when-downgrade' => $this->t('No Referrer When Downgrade'),
      'origin' => $this->t('Origin'),
      'origin-when-cross-origin' => $this->t('Origin When Cross-Origin'),
      'same-origin' => $this->t('Same Origin'),
      'strict-origin' => $this->t('Strict Origin'),
      'strict-origin-when-cross-origin' => $this->t('Strict Origin When Cross-Origin'),
      'unsafe-url' => $this->t('Unsafe URL'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestFormXpath(): array {
    return [
      // @todo This should work but it results in the following error:
      // DOMXPath::query(): Invalid predicate.
      // "//select[@name='{$this->id}'",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestFormData(): array {
    return [$this->id => 'no-referrer'];
  }

}
