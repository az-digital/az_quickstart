<?php

namespace Drupal\az_publication\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

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
      '#type' => 'textarea',
      '#title' => $this->t('Citation Style Language'),
      '#rows' => 15,
      '#default_value' => $az_citation_style->getStyle(),
      '#description' => $this->t('A stylesheet in Citation Style Language (CSL). For reference, consult the <a href="@csl">Citation Style Language project</a> and <a href="@csl-repo">GitHub repository</a>.', [
        '@csl' => 'https://citationstyles.org/',
        '@csl-repo' => 'https://github.com/citation-style-language/styles',
      ]),
      '#required' => TRUE,
    ];

    return $form;
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
