<?php

namespace Drupal\az_publication\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\Exception\CiteProcException;

/**
 * Class AZQuickstartCitationStyleForm provides a form for editing CSL styles.
 */
class AZQuickstartCitationStyleForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\az_publication\Entity\AZQuickstartCitationStyleInterface $az_citation_style */
    $az_citation_style = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $az_citation_style->label(),
      '#description' => $this->t("Label for the Quickstart Citation Style."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $az_citation_style->id(),
      '#machine_name' => [
        'exists' => '\Drupal\az_publication\Entity\AZQuickstartCitationStyle::load',
      ],
      '#disabled' => !$az_citation_style->isNew(),
    ];

    $form['style'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Citation Style Language Style'),
      '#size' => 60,
      '#maxlength' => 128,
      '#default_value' => $az_citation_style->getStyle(),
      '#description' => $this->t('The name of a CSL stylesheet in the <a href="@csl-repo">Citation Style Language GitHub repository</a>. Do not include the .csl file extension. E.g. for <strong>modern-language-association.csl</strong>, enter <strong>modern-language-association</strong>', [
        '@csl' => 'https://citationstyles.org/',
        '@csl-repo' => 'https://github.com/citation-style-language/styles',
      ]),
      '#required' => FALSE,
    ];

    $open = !empty($az_citation_style->getCustom());

    $form['custom_container'] = [
      '#type' => 'details',
      '#open' => $open,
      '#title' => $this
        ->t('Custom Citation Style Language'),
    ];

    $form['custom_container']['custom'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Citation Style Language'),
      '#rows' => 15,
      '#default_value' => $az_citation_style->getCustom(),
      '#description' => $this->t('A custom stylesheet in Citation Style Language (CSL). This field is only necessary if you have a custom citation style. For reference, consult the <a href="@csl">Citation Style Language project</a> and <a href="@csl-repo">GitHub repository</a>.', [
        '@csl' => 'https://citationstyles.org/',
        '@csl-repo' => 'https://github.com/citation-style-language/styles',
      ]),
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (empty($values['style']) && empty($values['custom'])) {
      $form_state->setErrorByName('style', $this->t('You must enter either a CSL stylesheet name or custom CSL.'));
    }
    if (!empty($values['style']) && !empty($values['custom'])) {
      $form_state->setErrorByName('style', $this->t('You must enter either a CSL stylesheet name or custom CSL, not both.'));
    }
    if (!empty($values['style'])) {
      try {
        $style = StyleSheet::loadStyleSheet($values['style']);
      }
      catch (CiteProcException $e) {
        $form_state->setErrorByName('style', $this->t('The stylesheet name is not valid.'));
      }
    }
    if (!empty($values['custom'])) {
      libxml_use_internal_errors(TRUE);
      $doc = simplexml_load_string($values['custom']);
      if ($doc === FALSE) {
        $form_state->setErrorByName('custom', $this->t('A custom CSL stylesheet must be valid XML.'));
      }
      libxml_clear_errors();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $az_citation_style = $this->entity;
    $status = $az_citation_style->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Quickstart Citation Style.', [
          '%label' => $az_citation_style->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Quickstart Citation Style.', [
          '%label' => $az_citation_style->label(),
        ]));
    }
    $form_state->setRedirectUrl($az_citation_style->toUrl('collection'));
    return $status;
  }

}
