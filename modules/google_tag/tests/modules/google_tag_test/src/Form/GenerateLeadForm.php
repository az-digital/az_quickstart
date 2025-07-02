<?php

declare(strict_types=1);

namespace Drupal\google_tag_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_tag\EventCollectorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test form for generating leads.
 */
final class GenerateLeadForm extends FormBase {

  /**
   * Collector.
   *
   * @var \Drupal\google_tag\EventCollectorInterface
   */
  protected EventCollectorInterface $collector;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->collector = $container->get('google_tag.event_collector');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'google_tag_test_generate_lead_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create content'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus('Lead generated');
    $this->collector->addEvent('generate_lead', [
      // No currency provided, falls back to default configuration.
      'value' => '100',
    ]);
  }

}
