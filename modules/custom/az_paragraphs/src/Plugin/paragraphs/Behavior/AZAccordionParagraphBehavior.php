<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides FAQ schema markup options for accordion paragraphs.
 *
 * @ParagraphsBehavior(
 *   id = "az_accordion_paragraph_behavior",
 *   label = @Translation("Quickstart Accordion Paragraph Behavior"),
 *   description = @Translation("Provides FAQ schema markup options for accordions."),
 *   weight = 0
 * )
 */
class AZAccordionParagraphBehavior extends AZDefaultParagraphsBehavior {

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getSettings($paragraph);

    $form['faq_schema'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('This is an FAQ'),
      '#default_value' => $config['faq_schema'] ?? FALSE,
      '#description' => $this->t('Enable FAQ (FAQPage) schema markup for this accordion. When checked, structured data will be added to the page so search engines can display these items as rich FAQ results. Only use this for content that is genuinely a list of frequently asked questions.'),
    ];

    parent::buildBehaviorForm($paragraph, $form, $form_state);

    return [];
  }

}
