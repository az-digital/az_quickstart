<?php

namespace Drupal\paragraphs_demo\Plugin\paragraphs\Conversion;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\Attribute\ParagraphsConversion;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsConversionBase;

/**
 * Provides a Paragraphs conversion plugin.
 */
#[ParagraphsConversion(
  id: "paragraphs_demo_user_to_text_user",
  label: new TranslatableMarkup("Convert to Text and User"),
  source_type: "user",
  target_types: ["text", "user"],
  weight: 0
)]
class ParagraphsDemoUserToTextUser extends ParagraphsConversionBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $settings, ParagraphInterface $original_paragraph, ?array $converted_paragraphs = NULL) {
    $username = "Empty user field";
    $user = NULL;
    if ($original_paragraph->get('field_user_demo')->entity) {
      $username = $original_paragraph->get('field_user_demo')->entity->label();
      $user = $original_paragraph->get('field_user_demo')->entity;
    }
    return [
      [
        'type' => 'text',
        'field_text_demo' => [
          'value' => $username,
        ],
      ],
      [
        'type' => 'user',
        'field_user_demo' => $user ?: NULL,
      ],
    ];
  }

}
