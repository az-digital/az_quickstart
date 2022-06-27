<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;

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

    // Card deck width for desktop.
    $form['card_width'] = [
      '#title' => $this->t('Cards per row on desktop'),
      '#type' => 'select',
      '#options' => [
        'col-lg-12' => $this->t('1'),
        'col-lg-6' => $this->t('2'),
        'col-lg-4' => $this->t('3'),
        'col-lg-3' => $this->t('4'),
      ],
      '#default_value' => $config['card_width'] ?? 'col-lg-4',
      '#description' => $this->t('Choose how many cards appear per row. Additional cards will wrap to a new row. This selection sets the cards per row on desktops with automatic defaults set for tablet and phone. Override cards per row on tablet and phone in Additional options.'),
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

    $form['card_clickable'] = [
      '#title' => $this->t('Clickable cards'),
      '#type' => 'checkbox',
      '#default_value' => isset($config['card_clickable']) ? $config['card_clickable'] : FALSE,
      '#description' => $this->t('Make the whole card clickable if the link fields are populated.'),
    ];

    parent::buildBehaviorForm($paragraph, $form, $form_state);

    // Card deck width for tablets.
    $form['az_display_settings']['card_width_sm'] = [
      '#title' => $this->t('Cards per row on tablet'),
      '#type' => 'select',
      '#options' => [
        'col-md-12' => $this->t('1'),
        'col-md-6' => $this->t('2'),
        'col-md-4' => $this->t('3'),
        'col-md-3' => $this->t('4'),
      ],
      '#default_value' => $config['az_display_settings']['card_width_sm'] ?? 'col-md-12',
      '#description' => $this->t('Choose how many cards appear per row. Additional cards will wrap to a new row. This selection sets the cards per row on tablets.'),
      '#weight' => 1,
    ];

    // Card deck width for phones.
    $form['az_display_settings']['card_width_xs'] = [
      '#title' => $this->t('Cards per row on phone'),
      '#type' => 'select',
      '#options' => [
        'col-12' => $this->t('1'),
        'col-6' => $this->t('2'),
        'col-4' => $this->t('3'),
        'col-3' => $this->t('4'),
      ],
      '#default_value' => $config['az_display_settings']['card_width_xs'] ?? 'col-12',
      '#description' => $this->t('Choose how many cards appear per row. Additional cards will wrap to a new row. This selection sets the cards per row on phones.'),
      '#weight' => 2,
    ];

    // This places the form fields on the content tab rather than behavior tab.
    // Note that form is passed by reference.
    // @see https://www.drupal.org/project/paragraphs/issues/2928759
    return [];
  }

}
