<?php

namespace Drupal\webform_schema\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Form\WebformEntityAjaxFormTrait;
use Drupal\webform\Utility\WebformDialogHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get webform schema.
 */
class WebformSchemaEntitySchemaForm extends EntityForm {

  use WebformEntityAjaxFormTrait;

  /**
   * The webform scheme manager.
   *
   * @var \Drupal\webform_schema\WebformSchemaManagerInterface
   */
  protected $schemaManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->schemaManager = $container->get('webform_schema.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $webform_ui_exists = $this->moduleHandler->moduleExists('webform_ui');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Header.
    $header = $this->schemaManager->getColumns();
    if ($webform_ui_exists) {
      $header['operations'] = $this->t('Operations');
    }

    // Rows.
    $rows = [];
    $elements = $this->schemaManager->getElements($webform);
    foreach ($elements as $element_key => $element) {
      $rows[$element_key] = [];

      foreach ($element as $key => $value) {
        if ($key === 'options_value' || $key === 'options_text') {
          $value = implode('; ', array_slice($value, 0, 12)) . (count($value) > 12 ? '; …' : '');
        }
        if (is_array($value)) {
          $rows[$element_key][$key] = $value;
        }
        else {
          $rows[$element_key][$key] = ['#markup' => $value];
        }
      }

      if ($element['datatype'] === 'Composite') {
        $rows[$element_key]['#attributes']['class'][] = 'webform-schema-composite';
      }

      if ($webform_ui_exists) {
        // Only add 'Edit' link to main element and not composite sub-elements.
        if (strpos($element_key, '.') === FALSE && $webform->getElement($element_key)) {
          $element_url = new Url(
            'entity.webform_ui.element.edit_form',
            ['webform' => $webform->id(), 'key' => $element_key],
            // Get destination without any Ajax wrapper parameters.
            ['query' => ['destination' => Url::fromRoute('<current>')->toString()]]
          );
          $rows[$element_key]['name'] = [
            '#type' => 'link',
            '#title' => $element_key,
            '#url' => $element_url,
            '#attributes' => WebformDialogHelper::getModalDialogAttributes(),
          ];
          $rows[$element_key]['operations'] = [
            '#type' => 'link',
            '#title' => $this->t('Edit'),
            '#url' => $element_url,
            '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NORMAL, ['button', 'button--small']),
          ];
        }
        else {
          $rows[$element_key]['operations'] = ['#markup' => ''];
        }

        // Add webform key used by Ajax callback.
        $rows[$element_key]['#attributes']['data-webform-key'] = explode('.', $element_key)[0];
      }
    }

    // Table.
    $form['schema'] = [
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => ['class' => ['webform-schema-table']],
    ] + $rows;

    WebformDialogHelper::attachLibraries($form);

    $form['#attached']['library'][] = 'webform_schema/webform_schema';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $actions = parent::actionsElement($form, $form_state);
    unset($actions['delete']);
    $actions['submit']['#value'] = $this->t('Export');
    $actions['reset']['#attributes']['style'] = 'display: none';
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('webform_schema.export', ['webform' => $this->getEntity()->id()]);
  }

}
