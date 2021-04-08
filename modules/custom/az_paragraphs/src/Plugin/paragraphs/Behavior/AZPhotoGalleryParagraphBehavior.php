<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

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
        'slider' => $this->t('Slider'),
        'grid' => $this->t('Grid'),
      ],
      '#required' => TRUE,
      '#default_value' => $config['gallery_display'] ?? 'slider',
      '#description' => $this->t('The type of display to use for the photo gallery.'),
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
  }

}
