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
 *   id = "az_split_screen",
 *   label = @Translation("Quickstart Split Screen Paragraph Behavior"),
 *   description = @Translation("Provides class selection for split screen."),
 *   weight = 0
 * )
 */
class AZSplitScreenParagraphBehavior extends AZDefaultParagraphsBehavior {

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getSettings($paragraph);

    $form['full_width'] = [
      '#title' => $this->t('Full Width'),
      '#type' => 'checkbox',
      '#default_value' => $config['full_width'] ?? '',
      '#description' => $this->t('Makes the background full width if checked.'),
      '#return_value' => 'full-width-background',
    ];

    $form['text_width'] = [
      '#title' => $this->t('Text Width'),
      '#type' => 'select',
      '#default_value' => $config['text_width'] ?? 'full_width',
      '#description' => $this->t('Determines the size of the text area.'),
      '#options' => [
        'full_width' => $this->t('Full Width'),
        'content_width' => $this->t('Content Width'),
      ],
    ];

    $form['ordering'] = [
      '#title' => $this->t('Image Order'),
      '#type' => 'select',
      '#default_value' => $config['ordering'] ?? 'order_0',
      '#description' => $this->t('Determines the ordering of the split screen image.'),
      '#options' => [
        'order_0' => $this->t('Image Left'),
        'order_1' => $this->t('Image Right'),
      ],
    ];

    $form['bg_color'] = [
      '#title' => $this->t('Background Color'),
      '#type' => 'select',
      '#options' => [
        'bg-white' => $this->t('White'),
        'bg-transparent' => $this->t('Transparent'),
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
        'bg-gray-100' => $this->t('Gray 100'),
        'bg-gray-200' => $this->t('Gray 200'),
        'bg-gray-300' => $this->t('Gray 300'),
        'bg-leaf' => $this->t('Leaf'),
        'bg-river' => $this->t('River'),
        'bg-silver' => $this->t('Silver'),
        'bg-ash' => $this->t('Ash'),
        'bg-mesa' => $this->t('Mesa'),
      ],
      '#default_value' => $config['bg_color'] ?? 'bg-white',
      '#description' => $this->t('<br><big><b>Important:</b></big> Site editors are responsible for accessibility and brand guideline considerations.<ul><li>To ensure proper color contrast, use the text color accessibility test at the bottom of the @arizona_bootstrap_color_docs_link.</li><li>For guidance on using the University of Arizona color palette, visit @ua_brand_colors_link.</li></ul>',
      [
        '@arizona_bootstrap_color_docs_link' => Link::fromTextAndUrl('Arizona Bootstrap color documentation', Url::fromUri('https://digital.arizona.edu/arizona-bootstrap/docs/2.0/getting-started/color-contrast/', ['attributes' => ['target' => '_blank']]))->toString(),
        '@ua_brand_colors_link' => Link::fromTextAndUrl('brand.arizona.edu/applying-the-brand/colors', Url::fromUri('https://brand.arizona.edu/applying-the-brand/colors', ['attributes' => ['target' => '_blank']]))->toString(),
      ]),
    ];

    $form['#after_build'][] = [get_class($this), 'afterBuild'];

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
    $variables['split_screen'] = $config;
  }

  public static function afterBuild(array $form, FormStateInterface $form_state) {
    $id = $form['full_width']['#id'];

    $form['text_width']['#states']['invisible'] = [
      ':input[id="' . $id . '"]' => ['checked' => FALSE],
    ];

    return $form;
  }

}
