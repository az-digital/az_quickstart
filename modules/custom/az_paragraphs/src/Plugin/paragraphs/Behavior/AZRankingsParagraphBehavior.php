<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a behavior for rankings.
 *
 * @ParagraphsBehavior(
 *   id = "az_rankings_paragraph_behavior",
 *   label = @Translation("Quickstart Rankings Paragraph Behavior"),
 *   description = @Translation("Provides class selection for rankings."),
 *   weight = 0
 * )
 */
class AZRankingsParagraphBehavior extends AZDefaultParagraphsBehavior {

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getSettings($paragraph);

    // Ranking deck width for desktop.
    $form['ranking_width'] = [
      '#title' => $this->t('Rankings per row on desktop'),
      '#type' => 'select',
      '#options' => [
        'col-lg-12' => $this->t('1'),
        'col-lg-6' => $this->t('2'),
        'col-lg-4' => $this->t('3'),
        'col-lg-3' => $this->t('4 (default)'),
      ],
      '#default_value' => $config['ranking_width'] ?? 'col-lg-3',
      '#description' => $this->t('Choose how many rankings appear per row. Additional rankings will wrap to a new row. This selection sets the rankings per row on desktops with automatic defaults set for tablet and phone. Override rankings per row on tablet and phone in Additional options.'),
    ];

    $form['ranking_alignment'] = [
      '#title' => $this->t('Ranking content alignment'),
      '#type' => 'select',
      '#options' => [
        'text-left' => $this->t('Left Aligned'),
        'text-center' => $this->t('Center Aligned'),
      ],
      '#default_value' => $config['ranking_alignment'] ?? 'text-left',
      '#description' => $this->t('Aligns the content within rankings left or centered.'),
    ];

    $form['ranking_header_style'] = [
      '#title' => $this->t('Ranking header style'),
      '#type' => 'select',
      '#options' => [
        'ranking-title-bold' => $this->t('Bold Headers'),
        'ranking-title-thin' => $this->t('Thin Headers'),
      ],
      '#default_value' => $config['ranking_header_style'] ?? 'ranking-title-bold',
      '#description' => $this->t('Uses large bold lettering or thin-styled font for headers'),
    ];

    $form['ranking_clickable'] = [
      '#title' => $this->t('Clickable rankings'),
      '#type' => 'checkbox',
      '#default_value' => $config['ranking_clickable'] ?? FALSE,
      '#description' => $this->t('Make the whole ranking clickable if the link fields are populated.'),
    ];

    $form['ranking_hover_effect'] = [
      '#title' => $this->t('Hover effect'),
      '#type' => 'checkbox',
      '#default_value' => $config['ranking_hover_effect'] ?? FALSE,
      '#description' => $this->t('Adds a contrasting hover effect.'),
      '#states' => [
        'visible' => [
          ':input[name*="[ranking_clickable]"]' => ['checked' => TRUE],
        ],
        // 'unchecked' => [
        //   ':input[name*="[ranking_clickable]"]' => ['unchecked' => TRUE],
        // ],
      ],
    ];

    parent::buildBehaviorForm($paragraph, $form, $form_state);

    // Ranking deck width for tablets.
    $form['az_display_settings']['ranking_width_sm'] = [
      '#title' => $this->t('Rankings per row on tablet'),
      '#type' => 'select',
      '#options' => [
        'col-md-12' => $this->t('1 (default)'),
        'col-md-6' => $this->t('2'),
        'col-md-4' => $this->t('3'),
        'col-md-3' => $this->t('4'),
      ],
      '#default_value' => $config['az_display_settings']['ranking_width_sm'] ?? 'col-md-12',
      '#description' => $this->t('Choose how many rankings appear per row. Additional rankings will wrap to a new row. This selection sets the rankings per row on tablets.'),
      '#weight' => 1,
    ];

    // Ranking deck width for phones.
    $form['az_display_settings']['ranking_width_xs'] = [
      '#title' => $this->t('Rankings per row on phone'),
      '#type' => 'select',
      '#options' => [
        'col-12' => $this->t('1 (default)'),
        'col-6' => $this->t('2'),
        'col-4' => $this->t('3'),
        'col-3' => $this->t('4'),
      ],
      '#default_value' => $config['az_display_settings']['ranking_width_xs'] ?? 'col-12',
      '#description' => $this->t('Choose how many rankings appear per row. Additional rankings will wrap to a new row. This selection sets the rankings per row on phones.'),
      '#weight' => 2,
    ];

    // Ranking deck title color.
    $form['ranking_deck_title_color'] = [
      '#title' => $this->t('Rankings group title color'),
      '#type' => 'select',
      '#options' => [
        'text-blue' => $this->t('Arizona Blue (default)'),
        'text-sky' => $this->t('Sky'),
        'text-oasis' => $this->t('Oasis'),
        'text-azurite' => $this->t('Azurite'),
        'text-midnight' => $this->t('Midnight'),
        'text-ash' => $this->t('Ash'),
        'text-dark-silver' => $this->t('Dark Silver'),
        'text-black' => $this->t('Black'),
        'text-white' => $this->t('White'),
      ],
      '#default_value' => $config['ranking_deck_title_color'] ?? 'text-blue',
      '#description' => $this->t('Change the color of the Ranking group title.'),
    ];

    // This places the form fields on the content tab rather than behavior tab.
    // Note that form is passed by reference.
    // @see https://www.drupal.org/project/paragraphs/issues/2928759
    return [];
  }

}
