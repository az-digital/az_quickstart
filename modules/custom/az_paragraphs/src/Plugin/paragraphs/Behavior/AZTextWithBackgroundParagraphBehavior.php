<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a behavior for text with background.
 *
 * @ParagraphsBehavior(
 *   id = "az_text_background_paragraph_behavior",
 *   label = @Translation("Quickstart Text with Background Paragraph Behavior"),
 *   description = @Translation("Provides class selection for text with background."),
 *   weight = 0
 * )
 */
class AZTextWithBackgroundParagraphBehavior extends AZDefaultParagraphsBehavior {

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getSettings($paragraph);

    // text_background deck width for desktop.
    $form['text_background_width'] = [
      '#title' => $this->t('text_backgrounds per row on desktop'),
      '#type' => 'select',
      '#options' => [
        'col-md-12 col-lg-12' => $this->t('1'),
        'col-md-6 col-lg-6' => $this->t('2'),
        'col-md-4 col-lg-4' => $this->t('3'),
        'col-md-3 col-lg-3' => $this->t('4'),
      ],
      '#default_value' => $config['text_background_width'] ?? 'col-md-4 col-lg-4',
      '#description' => $this->t('Choose how many text_backgrounds appear per row. Additional text_backgrounds will wrap to a new row. This selection sets the text_backgrounds per row on desktops with automatic defaults set for tablet and phone. Override text_backgrounds per row on tablet and phone in Additional options.'),
    ];

    $form['text_background_style'] = [
      '#title' => $this->t('text_background style'),
      '#type' => 'select',
      '#options' => [
        'text_background' => $this->t('Bordered text_backgrounds'),
        'text_background text_background-borderless' => $this->t('Borderless text_backgrounds'),
      ],
      '#default_value' => isset($config['text_background_style']) ? $config['text_background_style'] : 'text_background',
      '#description' => $this->t('Select a text_background style.'),
    ];

    parent::buildBehaviorForm($paragraph, $form, $form_state);

    // text_background deck width for tablets.
    $form['az_display_settings']['text_background_width_sm'] = [
      '#title' => $this->t('text_backgrounds per row on tablet'),
      '#type' => 'select',
      '#options' => [
        'col-sm-12' => $this->t('1'),
        'col-sm-6' => $this->t('2'),
        'col-sm-4' => $this->t('3'),
        'col-sm-3' => $this->t('4'),
      ],
      '#default_value' => $config['az_display_settings']['text_background_width_sm'] ?? 'col-sm-6',
      '#description' => $this->t('Choose how many text_backgrounds appear per row. Additional text_backgrounds will wrap to a new row. This selection sets the text_backgrounds per row on tablets.'),
      '#weight' => 1,
    ];

    // text_background deck width for phones.
    $form['az_display_settings']['text_background_width_xs'] = [
      '#title' => $this->t('text_backgrounds per row on phone'),
      '#type' => 'select',
      '#options' => [
        'col-12' => $this->t('1'),
        'col-6' => $this->t('2'),
        'col-4' => $this->t('3'),
        'col-3' => $this->t('4'),
      ],
      '#default_value' => $config['az_display_settings']['text_background_width_xs'] ?? 'col-12',
      '#description' => $this->t('Choose how many text_backgrounds appear per row. Additional text_backgrounds will wrap to a new row. This selection sets the text_backgrounds per row on phones.'),
      '#weight' => 2,
    ];

    // This places the form fields on the content tab rather than behavior tab.
    // Note that form is passed by reference.
    // @see https://www.drupal.org/project/paragraphs/issues/2928759
    return [];
  }

}
