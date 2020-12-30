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

    $form['text_background_title'] = [
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#default_value' => $config['text_background_title'] ?? '',
      '#description' => $this->t('Title of the Paragraph'),
    ];

    $form['text_background_title_centered'] = [
      '#title' => $this->t('Centered'),
      '#type' => 'checkbox',
      '#default_value' => $config['text_background_title_centered'] ?? '',
      '#description' => $this->t('Centers the title if checked.'),
      '#return_value' => 'text-center',
    ];

    parent::buildBehaviorForm($paragraph, $form, $form_state);

    $form['az_display_settings']['text_background_color'] = [
      '#title' => $this->t('Background Color'),
      '#type' => 'select',
      '#options' => [
        '' => $this->t('None'),
        'bg-red' => $this->t('Arizona Red'),
        'bg-blue' => $this->t('Arizona Blue'),
        'bg-sky' => $this->t('Sky'),
        'bg-oasis' => $this->t('Oasis'),
        'bg-azurite' => $this->t('Azurite'),
        'bg-midnight' => $this->t('Midnight'),
        'bg-bloom' => $this->t('Bloom'),
        'bg-chili' => $this->t('Chili'),
        'bg-cool-gray' => $this->t('Cool Gray'),
        'bg-warm-gray' => $this->t('Warm Gray'),
        'bg-leaf' => $this->t('Leaf'),
        'bg-river' => $this->t('River'),
        'bg-silver' => $this->t('Silver'),
        'bg-ash' => $this->t('Ash'),
      ],
      '#default_value' => $config['text_background_color'] ?? '',
      '#description' => $this->t('<br><big><b>Important:</b></big> Site editors are responsible for accessibility and brand guideline considerations.<ul><li>To ensure proper color contrast, use the text color accessibility test at the bottom of the <a href="http://uadigital.arizona.edu/ua-bootstrap/colors.html" target="_blank">UA Bootstrap color documentation</a>.</li><li>For guidance on using the University of Arizona color palette, visit <a href="https://brand.arizona.edu/ua-color-palette" target="_blank">brand.arizona.edu</a>.</li></ul>'),
    ];

    $form['az_display_settings']['text_background_pattern'] = [
      '#title' => $this->t('Background Pattern'),
      '#type' => 'select',
      '#options' => [
        '' => $this->t('None'),
        'bg-triangles-left' => $this->t('Triangles Left'),
        'bg-triangles-centered' => $this->t('Triangles Centered'),
        'bg-triangles-right' => $this->t('Triangles Right'),
        'bg-trilines' => $this->t('Trilines'),
      ],
      '#default_value' => isset($config['text_background_pattern']) ? $config['text_background_pattern'] : '',
      '#description' => $this->t('<br><big><b>Important:</b></big> Patterns are intended to be used sparingly.<ul><li>Please ensure sufficient contrast between text and its background.</li><li> More detail on background pattern options can be found in the <a href="http://uadigital.arizona.edu/ua-bootstrap/components.html#background-wrappers" target="_blank">UA Bootstrap background wrapper documentation</a>.</li>'),
    ];

    $form['az_display_settings']['text_background_full_width'] = [
      '#title' => $this->t('Full Width'),
      '#type' => 'checkbox',
      '#default_value' => $config['text_background_full_width'] ?? '',
      '#description' => $this->t('Makes the background full width if checked.'),
    ];

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
    $variables['text_with_background'] = $config;
  }

}
