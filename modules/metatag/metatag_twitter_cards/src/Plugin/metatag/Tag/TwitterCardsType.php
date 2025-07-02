<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards Type-tag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_type",
 *   label = @Translation("Twitter card type"),
 *   description = @Translation("Notes:<ul><li>no other fields are required for a Summary card</li><li>Media player card requires the 'title', 'description', 'media player URL', 'media player width', 'media player height' and 'image' fields,</li><li>Summary Card with Large Image card requires the 'Summary' field and the 'image' field,</li><li>App Card requires the 'iPhone app ID' field, the 'iPad app ID' field and the 'Google Play app ID' field,</li></ul>"),
 *   name = "twitter:card",
 *   group = "twitter_cards",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsType extends MetaNameBase {

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
      'summary' => $this->t('Summary Card'),
      'summary_large_image' => $this->t('Summary Card with large image'),
      'app' => $this->t('App Card'),
      'player' => $this->t('Player Card'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestFormXpath(): array {
    return ["//select[@name='twitter_cards_type']"];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestFormData(): array {
    return [
      // @todo Expand this?
      'twitter_cards_type' => 'summary',
    ];
  }

}
