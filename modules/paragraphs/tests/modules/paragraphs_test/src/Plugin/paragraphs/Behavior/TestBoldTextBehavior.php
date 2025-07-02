<?php

namespace Drupal\paragraphs_test\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\Attribute\ParagraphsBehavior;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Provides a test feature plugin.
 */
#[ParagraphsBehavior(
  id: 'test_bold_text',
  label: new TranslatableMarkup('Test bold page plugin'),
  description: new TranslatableMarkup('Test bold text plugin'),
  weight: 2,
)]
class TestBoldTextBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['bold_text'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bold Text'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'bold_text', FALSE),
      '#description' => $this->t("Bold text for the paragraph."),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    if ($paragraph->getBehaviorSetting($this->getPluginId(), 'bold_text')) {
      $build['#attributes']['class'][] = 'bold_plugin_text';
      $build['#attached']['library'][] = 'paragraphs_test/drupal.paragraphs_test.bold_text';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    // If the name of the field is not text_paragraph_test then allow using this
    // plugin.
    if ($paragraphs_type->id() != 'text_paragraph_test') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    $bold_setting = $paragraph->getBehaviorSetting($this->getPluginId(), 'bold_text');
    return [
      [
        'label' => $this->t('Bold'),
        'value' => $bold_setting ? $this->t('Yes') : $this->t('No')
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsIcon(Paragraph $paragraph) {
    $bold_setting = $paragraph->getBehaviorSetting($this->getPluginId(), 'bold_text');
    if ($bold_setting) {
      return [
        'bold' => [
          '#theme' => 'paragraphs_info_icon',
          '#message' => $this->t('Bold: Yes.'),
          '#icon' => 'bold',
        ],
      ];
    }
    return [];
  }

}
