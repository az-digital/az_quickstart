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
 *   id = "az_link_group",
 *   label = @Translation("Quickstart Link Group Paragraph Behavior"),
 *   description = @Translation("Provides class selection for link group."),
 *   weight = 0
 * )
 */
class AZLinkGroupParagraphBehavior extends AZDefaultParagraphsBehavior {

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getSettings($paragraph);

    // Link Group style
    $form['group_style'] = [
      '#title' => $this->t('Style'),
      '#type' => 'select',
      '#default_value' => $config['group_style'] ?? 'buttons',
      '#description' => $this->t('Determines the style/behavior of your group of links.'),
      '#options' => [
        'buttons' => $this->t('Buttons'),
        'dropdown' => $this->t('Dropdown'),
        'list_group' => $this->t('List Group'),
      ],
    ];

    // Dropdown title
    $form['dropdown_title'] = [
      '#title' => $this->t('Dropdown Title'),
      '#type' => 'textfield',
      '#description' => $this->t('The title of your dropdown menu.'),
      '#size' => 60,
      '#maxlength' => 120,
      '#default_value' => $config['dropdown_title'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="field_az_main_content[0][behavior_plugins][az_link_group][group_style]"]' => [
            ['value' => 'dropdown'],
          ],
        ],
      ],
      
    ];
    $form[":input[name='field_az_main_content[0][behavior_plugins][az_link_group][dropdown_title]']"]['target_id']['#states'] = [
      'required' => [
        ':input[name="field_az_main_content[0][behavior_plugins][az_link_group][group_style]"]' => [
          ['value' => 'dropdown'],
        ],
      ],
    ];

      // Button color
    $form['button_color'] = [
      '#title' => $this->t('Button Color'),
      '#type' => 'select',
      '#options' => [
        'btn-red' => $this->t('Red'),
        'btn-blue' => $this->t('Blue'),
        'btn-outline-red' => $this->t('Red Outline'),
        'btn-outline-blue' => $this->t('Blue Outline'),
        'btn-outline-white' => $this->t('White Outline'),
      ],
      '#default_value' => $config['button_color'] ?? 'btn-blue',
      '#description' => $this->t('<br><big><b>Important:</b></big> Site editors are responsible for accessibility and brand guideline considerations.<ul><li>To ensure proper color contrast, use the text color accessibility test at the bottom of the @arizona_bootstrap_color_docs_link.</li><li>For guidance on using the University of Arizona color palette, visit @ua_brand_colors_link.</li></ul>',
      [
        '@arizona_bootstrap_color_docs_link' => Link::fromTextAndUrl('Arizona Bootstrap color documentation', Url::fromUri('https://digital.arizona.edu/arizona-bootstrap/docs/2.0/getting-started/color-contrast/', ['attributes' => ['target' => '_blank']]))->toString(),
        '@ua_brand_colors_link' => Link::fromTextAndUrl('brand.arizona.edu/applying-the-brand/colors', Url::fromUri('https://brand.arizona.edu/applying-the-brand/colors', ['attributes' => ['target' => '_blank']]))->toString(),
      ]),
      '#states' => [
        'visible' => [
          ':input[name="field_az_main_content[0][behavior_plugins][az_link_group][group_style]"]' => [
            ['value' => 'buttons'],
            'or',
            ['value' => 'dropdown']
          ],
        ],
      ],
    ];

    // Button size
    $form['button_size'] = [
      '#title' => $this->t('Button Size'),
      '#type' => 'select',
      '#options' => [
        'default' => $this->t('Default'),
        'btn-lg' => $this->t('Large'),
        'btn-sm' => $this->t('Small'),
      ],
      '#default_value' => $config['button_size'] ?? 'default',
      '#states' => [
        'visible' => [
          ':input[name="field_az_main_content[0][behavior_plugins][az_link_group][group_style]"]' => [
            ['value' => 'buttons'],
            'or',
            ['value' => 'dropdown']
          ],
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

    // Get plugin configuration and save in vars for twig to use.
    $config = $this->getSettings($paragraph);
    $variables['link_group'] = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function validateBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    // Throw error if required fields not filled in

  }

}
