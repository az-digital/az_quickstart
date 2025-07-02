<?php

namespace Drupal\linkit\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\FileInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Drupal\linkit\Utility\LinkitHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'linkit' widget.
 *
 * @FieldWidget(
 *   id = "linkit",
 *   label = @Translation("Linkit"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkitWidget extends LinkWidget {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The linkit profile storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkitProfileStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs a new Linkit field widget.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   The widget third party settings.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountProxyInterface $currentUser, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->currentUser = $currentUser;
    $this->linkitProfileStorage = $entityTypeManager->getStorage('linkit_profile');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'linkit_profile' => 'default',
      'linkit_auto_link_text' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $options = array_map(function ($linkit_profile) {
      return $linkit_profile->label();
    }, $this->linkitProfileStorage->loadMultiple());

    $elements['linkit_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Linkit profile'),
      '#options' => $options,
      '#default_value' => $this->getSetting('linkit_profile'),
    ];
    $elements['linkit_auto_link_text'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically populate link text from entity label'),
      '#default_value' => $this->getSetting('linkit_auto_link_text'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $linkit_profile_id = $this->getSetting('linkit_profile');
    $linkit_profile = $this->linkitProfileStorage->load($linkit_profile_id);

    if ($linkit_profile) {
      $summary[] = $this->t('Linkit profile: @linkit_profile', ['@linkit_profile' => $linkit_profile->label()]);
    }

    $auto_link_text = $this->getSetting('linkit_auto_link_text') ? $this->t('Yes') : $this->t('No');
    $summary[] = $this->t(
      'Automatically populate link text from entity label: @auto_link_text',
      ['@auto_link_text' => $auto_link_text]
    );

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    /** @var \Drupal\link\LinkItemInterface $item */
    $item = $items[$delta];
    $uri = $item->uri ?? NULL;

    // Try to fetch entity information from the URI.
    $default_allowed = !$item->isEmpty() && ($this->currentUser->hasPermission('link to any page') || $item->getUrl()->access());
    if (!empty($item->options['data-entity-type']) && !empty($item->options['data-entity-uuid'])) {
      $entity = \Drupal::service('entity.repository')->loadEntityByUuid($item->options['data-entity-type'], $item->options['data-entity-uuid']);
    }
    else {
      $entity = $default_allowed && $uri ? LinkitHelper::getEntityFromUri($uri) : NULL;
    }
    // Display entity URL consistently across all entity types.
    if ($entity instanceof FileInterface) {
      // File entities are anomalies, so we handle them differently.
      $element['uri']['#default_value'] = \Drupal::service('file_url_generator')->generateString($entity->getFileUri());
    }
    elseif ($entity instanceof EntityInterface) {
      $uri_parts = parse_url($uri);
      $uri_options = [];
      // Extract query parameters and fragment and merge them into $uri_options.
      if (isset($uri_parts['fragment']) && $uri_parts['fragment'] !== '') {
        $uri_options += ['fragment' => $uri_parts['fragment']];
      }
      if (!empty($uri_parts['query'])) {
        $uri_query = [];
        parse_str($uri_parts['query'], $uri_query);
        $uri_options['query'] = isset($uri_options['query']) ? $uri_options['query'] + $uri_query : $uri_query;
      }
      $element['uri']['#default_value'] = $entity->toUrl()->setOptions($uri_options)->toString();
    }
    // Change the URI field to use the linkit profile.
    $element['uri']['#type'] = 'linkit';
    $element['uri']['#description'] = $this->t('Start typing to find content or paste a URL and click on the suggestion below.');
    $element['uri']['#autocomplete_route_name'] = 'linkit.autocomplete';
    $element['uri']['#autocomplete_route_parameters'] = [
      'linkit_profile_id' => $this->getSetting('linkit_profile'),
    ];

    // Add a class to the title field.
    $element['title']['#attributes']['class'][] = 'linkit-widget-title';
    if ($this->getSetting('linkit_auto_link_text')) {
      $element['title']['#attributes']['data-linkit-widget-title-autofill-enabled'] = TRUE;
    }

    // Add linkit specific attributes.
    $element['attributes']['href'] = [
      '#type' => 'hidden',
      '#default_value' => $default_allowed ? $uri : '',
    ];
    $element['attributes']['data-entity-type'] = [
      '#type' => 'hidden',
      '#default_value' => $entity ? $entity->getEntityTypeId() : '',
    ];
    $element['attributes']['data-entity-uuid'] = [
      '#type' => 'hidden',
      '#default_value' => $entity ? $entity->uuid() : '',
    ];
    $element['attributes']['data-entity-substitution'] = [
      '#type' => 'hidden',
      '#default_value' => $entity ? ($entity->getEntityTypeId() === 'file' ? 'file' : 'canonical') : '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value['uri'] = LinkitHelper::uriFromUserInput($value['uri']);
      $value += ['options' => $value['attributes']];
    }
    return $values;
  }

}
