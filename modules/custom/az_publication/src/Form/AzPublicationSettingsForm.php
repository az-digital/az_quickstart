<?php

namespace Drupal\az_publication\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for Publication settings.
 */
class AzPublicationSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'az_publication.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_publication_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    // Build option list of citation styles.
    $styles = $this->entityTypeManager->getStorage('az_citation_style')->loadMultiple();
    foreach ($styles as $name => $style) {
      $options[$name] = $style->label();
    }
    $form['default_citation_style'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Default Citation Style'),
      '#description' => $this->t('Select the default citation style to use. This will be used unless another citation style is selected.'),
      '#options' => $options,
      '#config_target' => 'az_publication.settings:default_citation_style',
    ];
    return parent::buildForm($form, $form_state);
  }

}
