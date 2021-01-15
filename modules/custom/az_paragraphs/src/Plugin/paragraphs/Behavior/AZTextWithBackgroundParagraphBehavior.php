<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
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

    $form['text_background_full_width'] = [
      '#title' => $this->t('Full Width'),
      '#type' => 'checkbox',
      '#default_value' => $config['text_background_full_width'] ?? '',
      '#description' => $this->t('Makes the background full width if checked.'),
      '#return_value' => 'full-width-background',
    ];

    $form['text_background_color'] = [
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
      '#description' => $this->t('<br><big><b>Important:</b></big> Site editors are responsible for accessibility and brand guideline considerations.<ul><li>To ensure proper color contrast, use the text color accessibility test at the bottom of the @arizona_bootstrap_color_docs_link.</li><li>For guidance on using the University of Arizona color palette, visit @ua_brand_colors_link.</li></ul>',
      [
        '@arizona_bootstrap_color_docs_link' => Link::fromTextAndUrl('Arizona Bootstrap color documentation', Url::fromUri('https://digital.arizona.edu/arizona-bootstrap/docs/2.0/getting-started/color-contrast/', ['attributes' => ['target' => '_blank']]))->toString(),
        '@ua_brand_colors_link' => Link::fromTextAndUrl('brand.arizona.edu/applying-the-brand/colors', Url::fromUri('https://brand.arizona.edu/applying-the-brand/colors', ['attributes' => ['target' => '_blank']]))->toString(),
      ]),
    ];

    $form['text_background_pattern'] = [
      '#title' => $this->t('Background Pattern'),
      '#type' => 'select',
      '#options' => [
        '' => $this->t('None'),
        'bg-triangles-top-left' => $this->t('Triangles Left'),
        'bg-triangles-centered' => $this->t('Triangles Centered'),
        'bg-triangles-top-right' => $this->t('Triangles Right'),
        'bg-trilines' => $this->t('Trilines'),
      ],
      '#default_value' => $config['text_background_pattern'] ?? '',
      '#description' => $this->t('<br><big><strong>Important:</strong></big> Patterns are intended to be used sparingly.<ul><li>Please ensure sufficient contrast between text and its background.</li><li> More detail on background pattern options can be found in the @arizona_bootstrap_docs_bg_wrappers_link.</li></ul>',
        [
          '@arizona_bootstrap_docs_bg_wrappers_link' => Link::fromTextAndUrl('Arizona Bootstrap Background Wrappers documentation', Url::fromUri('https://digital.arizona.edu/arizona-bootstrap/docs/2.0/components/background-wrappers/', ['attributes' => ['target' => '_blank']]))->toString(),
        ]),
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
    $variables['text_with_background'] = $config;
  }

}
