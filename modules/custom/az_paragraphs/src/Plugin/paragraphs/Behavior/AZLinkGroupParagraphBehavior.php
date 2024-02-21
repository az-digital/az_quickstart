<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Provides a behavior for link group paragraphs.
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
    $linkgroup_style_unique_id = Html::getUniqueId('');

    // Link Group style.
    $form['group_style'] = [
      '#title' => $this->t('Style'),
      '#type' => 'select',
      '#default_value' => $config['group_style'] ?? 'buttons',
      '#description' => $this->t('Determines the style/behavior of your group of links.'),
      '#options' => [
        'buttons' => $this->t('Inline Buttons'),
        'dropdown' => $this->t('Dropdown Button'),
        'list_group' => $this->t('List Group'),
      ],
      '#attributes' => [
        'id' => $linkgroup_style_unique_id,
      ],
    ];

    // Dropdown title.
    $form['dropdown_title'] = [
      '#title' => $this->t('Dropdown Button Title'),
      '#type' => 'textfield',
      '#description' => $this->t('The title of your dropdown menu.'),
      '#size' => 60,
      '#maxlength' => 120,
      '#default_value' => $config['dropdown_title'] ?? '',
      '#states' => [
        'visible' => [
          ':input[id="' . $linkgroup_style_unique_id . '"]' => ['value' => 'dropdown'],
        ],
        'required' => [
          ':input[id="' . $linkgroup_style_unique_id . '"]' => ['value' => 'dropdown'],
        ],
      ],
    ];

    // Button color.
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
      '#states' => [
        'visible' => [
          ':input[id="' . $linkgroup_style_unique_id . '"]' => [
            ['value' => 'buttons'],
            'or',
            ['value' => 'dropdown'],
          ],
        ],
      ],
    ];

    // Button size.
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
          ':input[id="' . $linkgroup_style_unique_id . '"]' => [
            ['value' => 'buttons'],
            'or',
            ['value' => 'dropdown'],
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

}
