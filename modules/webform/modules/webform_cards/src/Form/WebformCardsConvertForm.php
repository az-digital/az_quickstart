<?php

namespace Drupal\webform_cards\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Form for converting wizard pages to cards.
 */
class WebformCardsConvertForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_cards_convert_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL) {
    $form['#title'] = $this->t('Convert @title wizard pages to cards', ['@title' => $webform->label()]);
    $form['webform_id'] = [
      '#type' => 'value',
      '#value' => $webform->id(),
    ];
    $form['warning'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('Please make sure to test the converted webform on a staging server before using cards in production.'),
      '#message_type' => 'warning',
    ];
    $form['description'] = [
      '#markup' => '<p>' . $this->t('Cards provide an almost identical user experience to wizard pages, but moving between cards is much faster. Cards use JavaScript for pagination and client-side validation. Cards also support auto-forwarding with conditional logic.') . '</p>' .
        '<p><em>' . $this->t('Please note: More complex webform elements may still require server-side validation.') . '</em></p>',
    ];
    $form['hr'] = ['#markup' => '<hr class="webform-hr"/>'];
    $form['confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Please confirm that you want to convert this webform's wizard pages to cards"),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Convert'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load($form_state->getValue('webform_id'));
    $elements = $webform->getElementsRaw();
    $elements = str_replace("'#type': webform_wizard_page", "'#type': webform_card", $elements);
    $webform->setElements(Yaml::decode($elements));
    $webform->save();

    $this->messenger()->addStatus($this->t('Wizard pages have been successfully converted to cards.'));
    $form_state->setRedirectUrl($webform->toUrl('edit-form'));
  }

}
