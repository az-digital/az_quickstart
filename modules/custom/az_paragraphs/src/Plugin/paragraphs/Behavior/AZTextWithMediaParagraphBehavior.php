<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a behavior for text with media.
 *
 * @ParagraphsBehavior(
 *   id = "az_text_media_paragraph_behavior",
 *   label = @Translation("Quickstart Text with media Paragraph Behavior"),
 *   description = @Translation("Provides class selection for text with media."),
 *   weight = 0
 * )
 */
class AZTextWithMediaParagraphBehavior extends AZDefaultParagraphsBehavior {

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getSettings($paragraph);

    $form['style'] = [
      '#title' => $this->t('Style'),
      '#type' => 'select',
      '#options' => [
        'plain' => $this->t('Plain'),
        'column' => $this->t('Column style'),
        'box' => $this->t('Box style'),
        'bottom' => $this->t('Bottom style'),
      ],
      '#default_value' => $config['style'] ?? '',
      '#description' => $this->t('The style of the text background'),
    ];

    $form['position'] = [
      '#title' => $this->t('Text position'),
      '#type' => 'select',
      '#options' => [
        'col-md-8 col-lg-6' => $this->t('Offset left'),
        'col-md-8 col-lg-6 col-md-offset-2 col-lg-offset-3' => $this->t('Offset center'),
        'col-md-8 col-lg-6 col-md-offset-4 col-lg-offset-6' => $this->t('Offset right'),
        'col-xs-12' => $this->t('No offset'),
      ],
      '#default_value' => $config['position'] ?? '',
      '#description' => $this->t('The position of the text content on the background'),
    ];

    $form['bg_color'] = [
      '#title' => $this->t('Text background color'),
      '#type' => 'select',
      '#options' => [
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
        'transparent' => $this->t('Transparent'),
      ],
      '#default_value' => $config['bg_color'] ?? '',
      '#description' => $this->t('The color of the text background'),
    ];

    $form['bg_attachment'] = [
      '#title' => $this->t('Background media attachment'),
      '#type' => 'select',
      '#options' => [
        'bg-fixed' => $this->t('Fixed'),
      ],
      '#empty_option' => $this->t('Scroll'),
      '#default_value' => $config['bg_attachment'] ?? '',
    ];

    $form['full_width'] = [
      '#title' => $this->t('Make background media full-width'),
      '#type' => 'checkbox',
      '#default_value' => $config['full_width'] ?? '',
      '#return_value' => 'full-width-background',
    ];

    parent::buildBehaviorForm($paragraph, $form, $form_state);

    // This places the form fields on the content tab rather than behavior tab.
    // Note that form is passed by reference.
    // @see https://www.drupal.org/project/paragraphs/issues/2928759
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(&$variables) {
    parent::preprocess($variables);

    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];

    // Get plugin configuration and save in vars for twig to use.
    $config = $this->getSettings($paragraph);
    $variables['text_on_media'] = $config;
  }

}
