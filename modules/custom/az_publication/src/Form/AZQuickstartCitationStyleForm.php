<?php

namespace Drupal\az_publication\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\StyleSheet;
use Symfony\Component\HttpFoundation\Request;

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

    $custom = $form_state->getValue('custom') ?? $az_citation_style->getCustom();

    // Present a text area or text field depending on the custom checkbox.
    if (!$custom) {
      // Element for referencing a CSL style by name.
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
      ];
    }
    else {
      // Element for entering custom CSL.
      $form['style'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Citation Style Language'),
        '#rows' => 15,
        '#default_value' => $az_citation_style->getStyle(),
        '#description' => $this->t('A custom stylesheet in Citation Style Language (CSL). For reference, consult the <a href="@csl">Citation Style Language project</a> and <a href="@csl-repo">GitHub repository</a>.', [
          '@csl' => 'https://citationstyles.org/',
          '@csl-repo' => 'https://github.com/citation-style-language/styles',
        ]),
      ];
    }

    $form['style'] += [
      '#required' => TRUE,
      '#prefix' => '<div id="style-wrapper">',
      '#suffix' => '</div>',
    ];

    // Checkbox triggers AJAX change of style element type.
    $form['custom'] = [
      '#type' => 'checkbox',
      '#title' => $this
        ->t('This style uses custom Citation Style Language'),
      '#default_value' => $az_citation_style->getCustom(),
      '#ajax' => [
        'callback' => '::customCheckboxCallback',
        'disable-refocus' => TRUE,
        'event' => 'change',
        'wrapper' => 'style-wrapper',
      ],
    ];

    return $form;
  }

  /**
   * Ajax callback event.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state upon ajax submit.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object for the current request.
   *
   * @return mixed
   *   Must return AjaxResponse object or render array.
   */
  public function customCheckboxCallback(array &$form, FormStateInterface $form_state, Request $request) {

    return $form['style'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // If the user entered custom CSL, verify that it seems to be XML.
    if (!empty($values['custom'])) {
      libxml_use_internal_errors(TRUE);
      $doc = simplexml_load_string($values['style']);
      if ($doc === FALSE) {
        $form_state->setErrorByName('style', $this->t('A custom CSL stylesheet must be valid XML.'));
      }
      libxml_clear_errors();
    }
    // Otherwise, check if we can successfully load the style the user entered.
    else {
      try {
        $style = StyleSheet::loadStyleSheet($values['style']);
      }
      catch (CiteProcException $e) {
        $form_state->setErrorByName('style', $this->t('The stylesheet name is not valid.'));
      }
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
