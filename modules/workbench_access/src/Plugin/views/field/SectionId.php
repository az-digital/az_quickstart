<?php

namespace Drupal\workbench_access\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\workbench_access\Entity\SectionAssociationInterface;

/**
 * Field handler to present the section assigned to the node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("workbench_access_section_id")
 */
class SectionId extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['output_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Output format'),
      '#options' => [
        'label' => $this->t('Section label'),
        'id' => $this->t('Section id'),
      ],
      '#default_value' => $this->options['output_format'],
    ];
    $form['make_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to Section entity'),
      '#default_value' => $this->options['make_link'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['output_format'] = [
      'default' => 'label',
    ];
    $options['make_link'] = [
      'default' => FALSE,
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = '';
    if ($entity = $this->getEntity($values)) {
      if ($entity instanceof SectionAssociationInterface) {
        $scheme_id = $entity->getSchemeId();
        $section_id = $entity->get('section_id')->value;
        // @todo We need a helper method or service for this lookup.
        // @phpstan-ignore-next-line
        $scheme = \Drupal::entityTypeManager()
          ->getStorage('access_scheme')
          ->load($scheme_id)
          ->getAccessScheme();
        if ($section = $scheme->load($section_id)) {
          if ($this->options['make_link'] && isset($section['path'])) {
            // Sigh. THe views handlers expect URLs in different formats.
            $this->options['alter']['url'] = Url::fromUserInput('/' . trim($section['path'], '/'));
            $this->options['alter']['make_link'] = TRUE;
          }
          if ($this->options['output_format'] === 'label') {
            $value = $this->sanitizeValue($section['label']);
          }
          else {
            $value = $this->sanitizeValue($section_id);
          }
        }
      }
    }

    return $value;
  }

}
