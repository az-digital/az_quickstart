<?php

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a form for creating and editing xmlsitemap entities.
 */
class XmlSitemapForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_sitemap_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if ($this->entity->getContext() == NULL) {
      $this->entity->context = [];
    }
    $xmlsitemap = $this->entity;
    $form['#entity'] = $xmlsitemap;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $xmlsitemap->label(),
      '#description' => $this->t('Label for the XML Sitemap.'),
      '#required' => TRUE,
    ];
    $form['context'] = [
      '#tree' => TRUE,
    ];

    if (!xmlsitemap_get_context_info()) {
      $form['context']['empty'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('There are currently no XML Sitemap contexts available.') . '</p>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if (!$form_state->hasValue('context')) {
      $form_state->setValue('context', xmlsitemap_get_current_context());
    }
    if ($form_state->hasValue(['context', 'language'])) {
      $language = $form_state->getValue(['context', 'language']);
      if ($language == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
        $form_state->unsetValue(['context', 'language']);
      }
    }
    $context = $form_state->getValue('context');
    $this->entity->context = $context;
    $this->entity->label = $form_state->getValue('label');
    $this->entity->id = xmlsitemap_sitemap_get_context_hash($context);

    try {
      $status = $this->entity->save();
      if ($status == SAVED_NEW) {
        $this->messenger()->addStatus($this->t('Saved the %label sitemap.', [
          '%label' => $this->entity->label(),
        ]));
      }
      elseif ($status == SAVED_UPDATED) {
        $this->messenger()->addStatus($this->t('Updated the %label sitemap.', [
          '%label' => $this->entity->label(),
        ]));
      }
    }
    catch (EntityStorageException $ex) {
      $this->messenger()->addError($this->t('There is another sitemap saved with the same context.'));
      $form_state->setRedirect('entity.xmlsitemap.add_form');
      return;
    }

    $form_state->setRedirect('xmlsitemap.admin_search');
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $request = $this->getRequest();
    if ($request->query->has('destination')) {
      $request->query->remove('destination');
    }
    $form_state->setRedirect('xmlsitemap.admin_delete', ['xmlsitemap' => $this->entity->id()]);
  }

}
