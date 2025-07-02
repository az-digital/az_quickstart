<?php

namespace Drupal\webform\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformSourceEntity\QueryStringWebformSourceEntity;
use Drupal\webform\WebformMessageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Link to webform' formatter.
 *
 * @FieldFormatter(
 *   id = "webform_entity_reference_link",
 *   label = @Translation("Link to form"),
 *   description = @Translation("Display link to the referenced webform."),
 *   field_types = {
 *     "webform"
 *   }
 * )
 */
class WebformEntityReferenceLinkFormatter extends WebformEntityReferenceFormatterBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The webform message manager.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface
   */
  protected $messageManager;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->messageManager = $container->get('webform.message_manager');
    $instance->tokenManager = $container->get('webform.token_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'label' => 'Go to [webform:title] webform',
      'dialog' => '',
      'attributes' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Label: @label', ['@label' => $this->getSetting('label')]);
    $dialog_option_name = $this->getSetting('dialog');
    if ($dialog_option = $this->configFactory->get('webform.settings')->get('settings.dialog_options.' . $dialog_option_name)) {
      $summary[] = $this->t('Dialog: @dialog', ['@dialog' => ($dialog_option['title'] ?? $dialog_option_name)]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    if ($this->fieldDefinition->getTargetEntityTypeId() === 'paragraph') {
      $form['message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t("This paragraph field's main entity will be used as the webform submission's source entity."),
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_SESSION,
      ];
    }

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('label'),
      '#required' => TRUE,
    ];

    $dialog_options = $this->configFactory->get('webform.settings')->get('settings.dialog_options');
    if ($dialog_options) {
      $options = [];
      foreach ($dialog_options as $dialog_option_name => $dialog_option) {
        $options[$dialog_option_name] = $dialog_option['title'] ?? $dialog_option_name;
      }
      $form['dialog'] = [
        '#title' => $this->t('Dialog'),
        '#type' => 'select',
        '#empty_option' => $this->t('- Select dialog -'),
        '#default_value' => $this->getSetting('dialog'),
        '#options' => $options,
      ];
      $form['attributes'] = [
        '#type' => 'webform_element_attributes',
        '#title' => $this->t('Link'),
        '#default_value' => $this->getSetting('attributes'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $source_entity = $items->getEntity();
    $this->messageManager->setSourceEntity($source_entity);

    $elements = [];

    /** @var \Drupal\webform\WebformInterface[] $entities */
    $entities = $this->getEntitiesToView($items, $langcode);
    foreach ($entities as $delta => $entity) {
      // Do not display the webform if the current user can't create submissions.
      if ($entity->id() && !$entity->access('submission_create')) {
        continue;
      }

      if ($entity->isOpen()) {
        $link_label = $this->getSetting('label');
        if (strpos($link_label, '[webform_submission') !== FALSE) {
          $link_entity = WebformSubmission::create([
            'webform_id' => $entity->id(),
            'entity_type' => $source_entity->getEntityTypeId(),
            'entity_id' => $source_entity->id(),
          ]);
          // Invoke override settings to all webform handlers to adjust any
          // form settings.
          $link_entity->getWebform()->invokeHandlers('overrideSettings', $link_entity);
        }
        else {
          $link_entity = $entity;
        }
        $link_options = QueryStringWebformSourceEntity::getRouteOptionsQuery($source_entity);
        $link = [
          '#type' => 'link',
          '#title' => ['#markup' => $this->tokenManager->replace($link_label, $link_entity)],
          '#url' => $entity->toUrl('canonical', $link_options),
          '#attributes' => $this->getSetting('attributes') ?: [],
        ];
        if ($dialog = $this->getSetting('dialog')) {
          $link['#attributes']['class'][] = 'webform-dialog';
          $link['#attributes']['class'][] = 'webform-dialog-' . $dialog;
          // Attach webform dialog library and options if they are not
          // on every page.
          if (!\Drupal::config('webform.settings')->get('settings.dialog')) {
            $link['#attached']['library'][] = 'webform/webform.dialog';
            $link['#attached']['drupalSettings']['webform']['dialog']['options'] = \Drupal::config('webform.settings')->get('settings.dialog_options');
          }
        }
        $elements[$delta] = $link;
      }
      else {
        $this->messageManager->setWebform($entity);
        $message_type = $entity->isOpening() ? WebformMessageManagerInterface::FORM_OPEN_MESSAGE : WebformMessageManagerInterface::FORM_CLOSE_MESSAGE;
        $elements[$delta] = $this->messageManager->build($message_type);
      }

      $this->setCacheContext($elements[$delta], $entity, $items[$delta]);
    }

    return $elements;
  }

}
