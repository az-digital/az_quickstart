<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a behavior for stats.
 *
 * @ParagraphsBehavior(
 *   id = "az_stats_paragraph_behavior",
 *   label = @Translation("Quickstart Stats Paragraph Behavior"),
 *   description = @Translation("Provides class selection for stats."),
 *   weight = 0
 * )
 */
class AZStatsParagraphBehavior extends AZDefaultParagraphsBehavior {

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getSettings($paragraph);

    // Stat deck width for desktop.
    $form['stat_width'] = [
      '#title' => $this->t('Rankings per row on desktop'),
      '#type' => 'select',
      '#options' => [
        'col-lg-12' => $this->t('1'),
        'col-lg-6' => $this->t('2'),
        'col-lg-4' => $this->t('3'),
        'col-lg-3' => $this->t('4'),
      ],
      '#default_value' => $config['stat_width'] ?? 'col-lg-3',
      '#description' => $this->t('Choose how many stats appear per row. Additional stats will wrap to a new row. This selection sets the stats per row on desktops with automatic defaults set for tablet and phone. Override stats per row on tablet and phone in Additional options.'),
    ];

    $form['stat_style'] = [
      '#title' => $this->t('Rankings Style'),
      '#type' => 'select',
      '#options' => [
        'card stat-bold-hover' => $this->t('Bold Hover'),
        'card stat-subtle-hover' => $this->t('Subtle Hover'),
        'card stat-thin' => $this->t('Static'),
      ],
      '#default_value' => $config['stat_style'] ?? 'card stat-bold-hover',
      '#description' => $this->t('Gives this ranking deck a bright hover effect, a button-like hover effect, or no effects when hovering over the card. For accessibility, when using a hover effect, Links are required.'),
    ];

    $form['stat_alignment'] = [
      '#title' => $this->t('Ranking Alignment'),
      '#type' => 'select',
      '#options' => [
        'text-left' => $this->t('Left Aligned'),
        'text-center' => $this->t('Center Aligned'),
      ],
      '#default_value' => $config['stat_alignment'] ?? 'text-left',
      '#description' => $this->t('Aligns the content within rankings left or centered.'),
    ];

    $form['stat_title_style'] = [
      '#title' => $this->t('Ranking Title Style'),
      '#type' => 'select',
      '#options' => [
        'stat-title-bold' => $this->t('Bold Headers'),
        'stat-title-thin' => $this->t('Thin Headers'),
      ],
      '#default_value' => $config['stat_title_style'] ?? 'stat-title-bold',
      '#description' => $this->t('Uses large bold lettering or thin-styled font for headers'),
    ];

    parent::buildBehaviorForm($paragraph, $form, $form_state);

    // Stat deck width for tablets.
    $form['az_display_settings']['stat_width_sm'] = [
      '#title' => $this->t('Rankings per row on tablet'),
      '#type' => 'select',
      '#options' => [
        'col-md-12' => $this->t('1'),
        'col-md-6' => $this->t('2'),
        'col-md-4' => $this->t('3'),
        'col-md-3' => $this->t('4'),
      ],
      '#default_value' => $config['az_display_settings']['stat_width_sm'] ?? 'col-md-6',
      '#description' => $this->t('Choose how many stats appear per row. Additional stats will wrap to a new row. This selection sets the stats per row on tablets.'),
      '#weight' => 1,
    ];

    // Stat deck width for phones.
    $form['az_display_settings']['stat_width_xs'] = [
      '#title' => $this->t('Rankings per row on phone'),
      '#type' => 'select',
      '#options' => [
        'col-12' => $this->t('1'),
        'col-6' => $this->t('2'),
        'col-4' => $this->t('3'),
        'col-3' => $this->t('4'),
      ],
      '#default_value' => $config['az_display_settings']['stat_width_xs'] ?? 'col-12',
      '#description' => $this->t('Choose how many stats appear per row. Additional stats will wrap to a new row. This selection sets the stats per row on phones.'),
      '#weight' => 2,
    ];

    // Stat deck title color.
    $form['stat_deck_title_color'] = [
      '#title' => $this->t('Rankings group title color'),
      '#type' => 'select',
      '#options' => [
        'text-blue' => $this->t('AZ Blue'),
        'text-black' => $this->t('Black'),
        'text-white' => $this->t('White'),
        'text-sky' => $this->t('Sky'),
        'text-oasis' => $this->t('Oasis'),
        'text-azurite' => $this->t('Azurite'),
        'text-midnight' => $this->t('Midnight'),
        'text-dark-silver' => $this->t('Dark Silver (default)'),
        'text-ash' => $this->t('Ash'),
      ],
      '#default_value' => $config['stat_deck_title_color'] ?? 'text-blue',
      '#description' => $this->t('Change the color of the Stat group title.'),
    ];

    // This places the form fields on the content tab rather than behavior tab.
    // Note that form is passed by reference.
    // @see https://www.drupal.org/project/paragraphs/issues/2928759
    return [];
  }

}
