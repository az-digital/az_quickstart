<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use Drupal\Component\Utility\Random;
use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The Favicons "mask-icon" meta tag.
 *
 * @MetatagTag(
 *   id = "mask_icon",
 *   label = @Translation("Mask icon (SVG)"),
 *   description = @Translation("A grayscale scalable vector graphic (SVG) file."),
 *   name = "mask-icon",
 *   group = "favicons",
 *   weight = 2,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MaskIcon extends LinkRelBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []): array {
    $form['#container'] = TRUE;
    $form['#tree'] = TRUE;

    // Backwards compatibility.
    $defaults = $this->value;
    if (is_string($defaults)) {
      $defaults = [
        'href' => $defaults,
        'color' => '',
      ];
    }

    // The main icon value.
    $form['href'] = [
      '#type' => 'textfield',
      '#title' => $this->label(),
      '#default_value' => $defaults['href'] ?? '',
      '#maxlength' => 255,
      '#required' => $element['#required'] ?? FALSE,
      '#description' => $this->description(),
      '#element_validate' => [[get_class($this), 'validateTag']],
    ];

    // New form element for color.
    $form['color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mask icon color'),
      '#default_value' => $defaults['color'] ?? '',
      '#required' => FALSE,
      '#description' => $this->t("Color attribute for SVG (mask) icon in hexadecimal format, e.g. '#0000ff'. Setting it will break HTML validation. If not set macOS Safari ignores the Mask Icon entirely, making the Icon: SVG completely useless."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function output(): array {
    $values = $this->value;

    // Make sure the value is an array, if it is not then assume it was assigned
    // before the "color" attribute was added, so place the original string as
    // the 'href' element and leave the 'color' element blank.
    if (!is_array($values)) {
      $values = [
        'href' => $values,
        'color' => '',
      ];
    }

    // Build the output.
    if (!empty($values['href'])) {
      $href = $this->tidy($values['href']);
      if ($href != '') {
        $element['#tag'] = 'link';
        $element['#attributes'] = [
          'rel' => $this->name(),
          'href' => $href,
        ];

        // Add the 'color' element.
        if (!empty($values['color'])) {
          $element['#attributes']['color'] = $this->tidy($values['color']);
        }

        return $element;
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value): void {
    // Do not store array with empty values.
    if (is_array($value) && empty(array_filter($value))) {
      $this->value = [];
    }
    else {
      $this->value = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTestFormXpath(): array {
    // This meta tag provides two separate form fields, so each needs to be
    // tested.
    return [
      "//input[@name='mask_icon[href]' and @type='text']",
      "//input[@name='mask_icon[color]' and @type='text']",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestFormData(): array {
    $random = new Random();
    return [
      // Use three alphanumeric strings joined with spaces.
      'mask_icon[href]' => 'https://www.example.com/images/' . $random->word(6) . '.gif',
      'mask_icon[color]' => '#b1ed9c',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputValuesXpath(array $values): array {
    return [
      "//link[@rel='mask-icon' and @href='{$values['mask_icon[href]']}' and @color='{$values['mask_icon[color]']}']",
    ];
  }

}
