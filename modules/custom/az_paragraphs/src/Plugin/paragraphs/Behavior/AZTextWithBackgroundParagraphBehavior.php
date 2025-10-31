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
        'text-bg-white' => $this->t('White'),
        'bg-transparent' => $this->t('Transparent'),
        'text-bg-red' => $this->t('Arizona Red'),
        'text-bg-blue' => $this->t('Arizona Blue'),
        'text-bg-sky' => $this->t('Sky'),
        'text-bg-oasis' => $this->t('Oasis'),
        'text-bg-azurite' => $this->t('Azurite'),
        'text-bg-midnight' => $this->t('Midnight'),
        'text-bg-bloom' => $this->t('Bloom'),
        'text-bg-chili' => $this->t('Chili'),
        'text-bg-cool-gray' => $this->t('Cool Gray'),
        'text-bg-warm-gray' => $this->t('Warm Gray'),
        'text-bg-gray-100' => $this->t('Gray 100'),
        'text-bg-gray-200' => $this->t('Gray 200'),
        'text-bg-gray-300' => $this->t('Gray 300'),
        'text-bg-leaf' => $this->t('Leaf'),
        'text-bg-river' => $this->t('River'),
        'text-bg-silver' => $this->t('Silver'),
        'text-bg-ash' => $this->t('Ash'),
        'text-bg-mesa' => $this->t('Mesa'),
      ],
      '#default_value' => $config['text_background_color'] ?? 'text-bg-white',
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

    $form['text_background_padding'] = [
      '#title' => $this->t('Space Around Content'),
      '#type' => 'select',
      '#options' => [
        'py-0' => $this->t('Zero'),
        'py-1' => $this->t('1 (0.25rem | ~4px)'),
        'py-2' => $this->t('2 (0.5rem | ~8px)'),
        'py-3' => $this->t('3 (1.0rem | ~16px)'),
        'py-4' => $this->t('4 (1.5rem | ~24px)'),
        'py-5' => $this->t('5 (3.0rem | ~48px) - Default'),
        'py-6' => $this->t('6 (4.0rem | ~64px)'),
        'py-7' => $this->t('7 (5.0rem | ~80px)'),
        'py-8' => $this->t('8 (6.0rem | ~96px)'),
        'py-9' => $this->t('9 (7.0rem | ~112px)'),
        'py-10' => $this->t('10 (8.0rem | ~128px)'),
        'py-20' => $this->t('20 (16.0rem | ~256px)'),
        'py-30' => $this->t('30 (24.0rem | ~384px)'),
      ],
      '#default_value' => $config['text_background_padding'] ?? 'py-5',
      '#description' => $this->t('Adds padding above and below the text.'),
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

    // If this paragraph is full-width, add the full-width library.
    if (isset($config['text_background_full_width']) && $config['text_background_full_width'] === 'full-width-background') {
      $variables['#attached']['library'][] = 'az_paragraphs/az_paragraphs.az_paragraphs_full_width';
    }

    // Add responsive padding classes.
    if (isset($config['text_background_padding'])) {
      $padding_classes = [];
      switch ($config['text_background_padding']) {
        case 'py-20':
          $padding_classes[] = 'py-10';
          $padding_classes[] = 'py-md-20';
          break;

        case 'py-30':
          $padding_classes[] = 'py-10';
          $padding_classes[] = 'py-md-30';
          break;

        default:
          $padding_classes[] = $config['text_background_padding'];
      }
      $config['text_background_padding'] = implode(' ', $padding_classes);
    }

    $variables['text_with_background'] = $config;
  }

}
