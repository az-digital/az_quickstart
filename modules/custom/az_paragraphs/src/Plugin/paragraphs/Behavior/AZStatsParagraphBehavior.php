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
      '#title' => $this->t('Stats per row on desktop'),
      '#type' => 'select',
      '#options' => [
        'col-lg-12' => $this->t('1'),
        'col-lg-6' => $this->t('2'),
        'col-lg-4' => $this->t('3'),
        'col-lg-3' => $this->t('4'),
      ],
      '#default_value' => $config['stat_width'] ?? 'col-lg-4',
      '#description' => $this->t('Choose how many stats appear per row. Additional stats will wrap to a new row. This selection sets the stats per row on desktops with automatic defaults set for tablet and phone. Override stats per row on tablet and phone in Additional options.'),
    ];

    // $form['stat_style'] = [
    //   '#title' => $this->t('Stat style'),
    //   '#type' => 'select',
    //   '#options' => [
    //     'stat' => $this->t('Bordered stats'),
    //     'stat border-0' => $this->t('Borderless stats'),
    //   ],
    //   '#default_value' => $config['stat_style'] ?? 'stat',
    //   '#description' => $this->t('Select a stat style.'),
    // ];

    // $form['stat_title_style'] = [
    //   '#title' => $this->t('Stat title style'),
    //   '#type' => 'select',
    //   '#options' => [
    //     'default' => $this->t('Title within stat (default)'),
    //     'title-on-image' => $this->t('Title on image'),
    //   ],
    //   '#default_value' => $config['stat_title_style'] ?? 'default',
    //   '#description' => $this->t('Select a stat title style.'),
    // ];


    $form['stat_style'] = [
      '#title' => $this->t('Style'),
      '#type' => 'select',
      '#options' => [
        'h3' => $this->t('Card Heading'),
        'stat-bold-static' => $this->t('Stat Bold Static'),
        'stat-bold-hover' => $this->t('Stat Bold Hover'),
      ],
      '#default_value' => $config['stat_style'] ?? 'stat-bold-hover',
      '#description' => $this->t('Gives the stats a bold look with an interactive hover effect, bold without the hover effect, or a more basic look similar to cards.'),
    ];



    // $form['stat_title_level'] = [
    //   '#title' => $this->t('Stat title level'),
    //   '#type' => 'select',
    //   '#options' => [
    //     'h2' => $this->t('Large block lettering'),
    //     'h3' => $this->t('H3 (Regular card heading)'),
//        'h4' => $this->t('H4 (subsection heading)'),
//        'h5' => $this->t('H5 (subsection heading)'),
//        'h6' => $this->t('H6 (subsection heading)'),
    //   ],
    //   '#default_value' => $config['stat_title_level'] ?? 'h3',
    //   '#description' => $this->t('The heading level of the stat title. <a href="https://quickstart.arizona.edu/best-practices/using-headings" target="_blank">Learn about best web practices</a>.'),
    // ];

    // $form['stat_title_display'] = [
    //   '#title' => $this->t('Stat title display size'),
    //   '#type' => 'select',
    //   '#options' => [
    //     'h6' => $this->t('Smallest (H6 size)'),
    //     'h5' => $this->t('Default (H5 size)'),
    //     'h4' => $this->t('Small (H4 size)'),
    //     'h3' => $this->t('Medium (H3 size)'),
    //     'h2' => $this->t('Large (H2 size)'),
    //     'display-4' => $this->t('Small Display Heading'),
    //     'display-3' => $this->t('Medium Display Heading'),
    //     'display-2' => $this->t('Large Display Heading'),
    //     'display-1' => $this->t('Largest Display Heading'),
    //   ],
    //   '#default_value' => $config['stat_title_display'] ?? 'h5',
    //   '#description' => $this->t('Select the display size of the title. <a href="https://digital.arizona.edu/arizona-bootstrap/docs/2.0/content/typography/#display-headings" target="_blank">Learn about display heading sizes</a>.'),
    // ];

    // $form['stat_clickable'] = [
    //   '#title' => $this->t('Clickable stats'),
    //   '#type' => 'checkbox',
    //   '#default_value' => $config['stat_clickable'] ?? FALSE,
    //   '#description' => $this->t('Make the whole stat clickable if the link fields are populated.'),
    // ];

    // $form['stat_hoverable'] = [
    //   '#title' => $this->t('Hoverable stats'),
    //   '#type' => 'checkbox',
    //   '#default_value' => $config['stat_hoverable'] ?? FALSE,
    //   '#description' => $this->t('Makes the stat use hover effects and colors based on each items color choice')
    // ];

    parent::buildBehaviorForm($paragraph, $form, $form_state);

    // Stat deck width for tablets.
    $form['az_display_settings']['stat_width_sm'] = [
      '#title' => $this->t('Stats per row on tablet'),
      '#type' => 'select',
      '#options' => [
        'col-md-12' => $this->t('1'),
        'col-md-6' => $this->t('2'),
        'col-md-4' => $this->t('3'),
        'col-md-3' => $this->t('4'),
      ],
      '#default_value' => $config['az_display_settings']['stat_width_sm'] ?? 'col-md-12',
      '#description' => $this->t('Choose how many stats appear per row. Additional stats will wrap to a new row. This selection sets the stats per row on tablets.'),
      '#weight' => 1,
    ];

    // Stat deck width for phones.
    $form['az_display_settings']['stat_width_xs'] = [
      '#title' => $this->t('Stats per row on phone'),
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
      '#title' => $this->t('Stat group title color'),
      '#type' => 'select',
      '#options' => [
        'text-dark-silver' => $this->t('Dark Silver (default)'),
        'text-blue' => $this->t('Blue'),
        'text-sky' => $this->t('Sky'),
        'text-oasis' => $this->t('Oasis'),
        'text-azurite' => $this->t('Azurite'),
        'text-midnight' => $this->t('Midnight'),
        'text-ash' => $this->t('Ash'),
        'text-black' => $this->t('Black'),
        'text-white' => $this->t('White'),
      ],
      '#default_value' => $config['stat_deck_title_color'] ?? 'text-dark-silver',
      '#description' => $this->t('Change the color of the Stat group title.'),
    ];

    // This places the form fields on the content tab rather than behavior tab.
    // Note that form is passed by reference.
    // @see https://www.drupal.org/project/paragraphs/issues/2928759
    return [];
  }

}
