<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a behavior for accordions.
 *
 * @ParagraphsBehavior(
 *   id = "az_accordion_paragraph_behavior",
 *   label = @Translation("Quickstart Accordion Paragraph Behavior"),
 *   description = @Translation("Provides additional options for accordions."),
 *   weight = 0
 * )
 */
class AZAccordionParagraphBehavior extends AZDefaultParagraphsBehavior {

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getSettings($paragraph);

    parent::buildBehaviorForm($paragraph, $form, $form_state);

    $form['expand_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Expand/Collapse All'),
      '#default_value' => $config['expand_all'] ?? FALSE,
      '#description' => $this->t('Display an Expand/Collapse All button above this accordion.'),
    ];

    // This places the form fields on the content tab rather than behavior tab.
    // Note that form is passed by reference.
    // @see https://www.drupal.org/project/paragraphs/issues/2928759
    return [];
  }

}
