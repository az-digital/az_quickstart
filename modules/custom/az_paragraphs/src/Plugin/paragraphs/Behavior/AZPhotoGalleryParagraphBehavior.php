<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a behavior for photo gallery paragraphs.
 *
 * @ParagraphsBehavior(
 *   id = "az_photo_gallery_paragraph_behavior",
 *   label = @Translation("Quickstart Photo Gallery Paragraph Behavior"),
 *   description = @Translation("Provides gallery type selection."),
 *   weight = 0
 * )
 */
class AZPhotoGalleryParagraphBehavior extends AZDefaultParagraphsBehavior {

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getSettings($paragraph);

    $form['gallery_display'] = [
      '#title' => $this->t('Photo Gallery Display'),
      '#type' => 'select',
      '#options' => [
        'grid' => $this->t('Grid'),
        'slider' => $this->t('Slider - Crop Image Style'),
        'slider_full' => $this->t('Slider - Full Image Style'),
      ],
      '#required' => TRUE,
      '#default_value' => $config['gallery_display'] ?? 'grid',
      '#description' => $this->t('The type of display to use for the photo gallery.'),
    ];

    $form['gallery_ratio'] = [
      '#title' => $this->t('Gallery Aspect Ratio'),
      '#type' => 'select',
      '#options' => [
        '1x1' => $this->t('1x1 (Square)'),
        '4x3' => $this->t('4x3 (Standard)'),
        '16x9' => $this->t('16x9 (Widescreen)'),
        '21x9' => $this->t('21x9 (Ultra-wide)'),
      ],
      '#default_value' => $config['gallery_ratio'] ?? '16x9',
      '#description' => $this->t('The aspect ratio for the photo gallery.'),
      '#states' => [
        'visible' => [
          ':input[name$="[behavior_plugins][az_photo_gallery_paragraph_behavior][gallery_display]"]' => ['value' => 'slider_full'],
        ],
        'required' => [
          ':input[name$="[behavior_plugins][az_photo_gallery_paragraph_behavior][gallery_display]"]' => ['value' => 'slider_full'],
        ],
      ],
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

    // Get the configuration for this paragraph.
    $config = $this->getSettings($paragraph);

    // Create a unique id for the carousel.
    $variables['gallery'] = Html::getUniqueId('az-photo-gallery-id');

    // Create a unique id for the modal, if needed.
    $variables['modal'] = Html::getUniqueId('az-photo-gallery-modal-id');

    // Variable to control which display to use 
    $variables['gallery_display'] = $config['gallery_display'] ?? 'grid';

    // Variable to control the aspect ratio for the gallery display.
    $variables['ratio'] = $config['gallery_ratio'] ?? '16x9';

  }

}
