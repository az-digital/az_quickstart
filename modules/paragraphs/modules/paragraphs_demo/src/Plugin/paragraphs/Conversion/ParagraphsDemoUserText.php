<?php

namespace Drupal\paragraphs_demo\Plugin\paragraphs\Conversion;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\Attribute\ParagraphsConversion;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsConversionBase;

/**
 * Provides a Paragraphs conversion plugin.
 */
#[ParagraphsConversion(
  id: "paragraphs_demo_user_to_text",
  label: new TranslatableMarkup("Convert to Text"),
  source_type: "user",
  target_types: ["text"],
  weight: 0
)]
class ParagraphsDemoUserText extends ParagraphsConversionBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $settings, ParagraphInterface $original_paragraph, ?array $converted_paragraphs = NULL) {
    $username = "Empty user field";
    if ($original_paragraph->get('field_user_demo')->entity) {
      $username = $original_paragraph->get('field_user_demo')->entity->label();
    }
    $converted_paragraphs = [
      [
        'type' => 'text',
        'field_text_demo' => [
          'value' => $username,
        ],
      ],
    ];
    if ($settings['multiple']) {
      $converted_paragraphs[] = [
        'type' => 'text',
        'field_text_demo' => [
          'value' => $username,
        ],
      ];
    }
    return $converted_paragraphs;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConversionForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form = [];
    $form['multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Multiple'),
      '#description' => $this->t('If selected the conversion will return two paragraphs.'),
    ];
    return $form;
  }

}
