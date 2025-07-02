<?php

namespace Drupal\paragraphs_test\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\Attribute\ParagraphsBehavior;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Provides a test feature plugin.
 */
#[ParagraphsBehavior(
  id: 'test_dummy_behavior',
  label: new TranslatableMarkup('Test dummy plugin'),
  description: new TranslatableMarkup('Test dummy plugin'),
  weight: 2,
)]
class TestDummyBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraphs_entity, EntityViewDisplayInterface $display, $view_mode) {
    $build['#attributes']['class'][] = 'dummy_plugin_text';
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    // Used to test that returning NULL does not return an error.
    return NULL;
  }
}
