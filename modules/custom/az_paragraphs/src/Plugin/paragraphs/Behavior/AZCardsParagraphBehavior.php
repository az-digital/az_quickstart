<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\az_paragraphs\Plugin\paragraphs\Behavior\AZDefaultParagraphsBehavior;

/**
 * Provides a behavior for cards.
 *
 * @ParagraphsBehavior(
 *   id = "az_cards_paragraph_behavior",
 *   label = @Translation("Quickstart Cards Paragraph Behavior"),
 *   description = @Translation("Provides class selection for cards."),
 *   weight = 0
 * )
 */
class AZCardsParagraphBehavior extends AZDefaultParagraphsBehavior {

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getSettings($paragraph);

    // Card deck width.
    $form['card_width'] = [
      '#title' => $this->t('Card width'),
      '#type' => 'select',
      '#options' => [
        'col-xs-12 col-sm-12 col-md-6 col-lg-3' => $this->t('Four cards per row'),
        'col-xs-12 col-sm-12 col-md-6 col-lg-4' => $this->t('Three cards per row'),
        'col-xs-12 col-sm-12 col-md-6 col-lg-6' => $this->t('Two cards per row'),
      ],
      '#default_value' => isset($config['card_width']) ? $config['card_width'] : 'col-xs-12 col-sm-12 col-md-6 col-lg-4',
      '#description' => $this->t('Choose how many cards appear per row.'),
    ];

    $form['card_style'] = [
      '#title' => $this->t('Card style'),
      '#type' => 'select',
      '#options' => [
        'card' => $this->t('Bordered cards'),
        'card card-borderless' => $this->t('Borderless cards'),
      ],
      '#default_value' => isset($config['card_style']) ? $config['card_style'] : 'card',
      '#description' => $this->t('Select a card style.'),
    ];

    parent::buildBehaviorForm($paragraph, $form, $form_state);

    // This places the form fields on the content tab rather than behavior tab.
    // Note that form is passed by reference.
    // @see https://www.drupal.org/project/paragraphs/issues/2928759
    return [];
  }

}
