<?php

namespace Drupal\field_group_link\Plugin\field_group\FieldGroupFormatter;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\field_group\FieldGroupFormatterBase;
use Drupal\file\Entity\File;
use \Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "link",
 *   label = @Translation("Link"),
 *   description = @Translation("Renders a field group as a link."),
 *   supported_contexts = {
 *     "view",
 *   },
 *   supported_link_field_types = {
 *    "link",
 *    "entity_reference",
 *    "file",
 *    "image",
 *   }
 * )
 */
class Link extends FieldGroupFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['group'],
      $configuration['settings'],
      $configuration['label'],
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('module_handler'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, $group, array $settings, $label, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ModuleHandlerInterface $module_handler, Token $token) {
    parent::__construct($plugin_id, $plugin_definition, $group, $settings, $label);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $module_handler;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = array(
      'target' => '_none',
      'classes' => '',
      'custom_uri' => '',
      'target_attribute' => 'default',
    ) + parent::defaultSettings($context);

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();

    $options = array(
      'entity' => $this->t('Full @entity_type page', array('@entity_type' => $this->group->entity_type)),
      'custom_uri' => $this->t('Custom URL'),
    );

    $fields = $this->entityFieldManager->getFieldDefinitions($this->group->entity_type, $this->group->bundle);
    foreach ($fields as $field) {
      if (in_array($field->getType(), $this->pluginDefinition['supported_link_field_types']) && $field->getFieldStorageDefinition()->isBaseField() == FALSE) {
        $options[$field->getName()] = $field->getLabel();
      }
    }

    $form['target'] = array(
      '#title' => $this->t('Link target'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('target'),
      '#options' => $options,
    );

    if (isset($this->group->group_name)) {
      $target_form_element = ':input[name="fields[' . $this->group->group_name . '][settings_edit_form][settings][target]"]';
    }
    else {
      // If no group name available, we are on the add-group form.
      $target_form_element = ':input[name="format_settings[target]"]';
    }
    $form['custom_uri'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Custom URL'),
      '#description' => $this->t('Tokens are supported (install the Token module to see a list of available Tokens).'),
      '#default_value' => $this->getSetting('custom_uri'),
      '#states' => array(
        'visible' => array(
          $target_form_element => array(array('value' => 'custom_uri')),
        ),
      ),
    );
    if ($this->moduleHandler->moduleExists('token')) {
      $form['tokens'] = array(
        '#title' => $this->t('Tokens'),
        '#type' => 'container',
        '#states' => array(
          'visible' => array(
            $target_form_element => array(array('value' => 'custom_uri')),
          ),
        ),
      );
      $form['tokens']['help'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => 'all',
        '#global_types' => FALSE,
        '#dialog' => TRUE,
      );
    }

    $form['target_attribute'] = array(
      '#title' => $this->t('Target attribute'),
      '#type' => 'select',
      '#options' => array('default' => $this->t('Default'), '_blank' => $this->t('Blank (new tab)')),
      '#default_value' => $this->getSetting('target_attribute'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    $entity = $this->getEntity($rendering_object);
    if (!$entity) {
      // We can't find the entity.
      // There's nothing we can do, so avoid attempting to create a link.
      return;
    }

    $url = NULL;

    switch ($this->getSetting('target')) {
      case 'entity':
        /** @var EntityInterface $entity */
        if (!$entity->isNew()) {
          $url = $entity->toUrl();
        }
        break;

      case 'custom_uri':
        $uri = $this->getSetting('custom_uri');
        $uri = $this->token->replace($uri, array($this->group->entity_type => $entity), array('clear' => TRUE, 'sanitize' => TRUE));
        try {
          $url = Url::fromUri($uri);
        }
        catch (\InvalidArgumentException $e) {
          return;
        }
        break;

      default:
        $url = $this->getUrlFromField($entity);
    }

    if ($url) {
      $element += array(
        '#type' => 'field_group_link',
        '#url' => $url,
        '#options' => array(
          'attributes' => array(
            'class' => $this->getClasses(),
          ),
        ),
      );

      $target_attribute = $this->getSetting('target_attribute');
      if ($target_attribute && $target_attribute != 'default') {
        $element['#options']['attributes']['target'] = $target_attribute;

      }
      if (!empty($this->getSetting('id'))) {
        $element['#options']['attributes']['id'] = $this->getSetting('id');
      }

      // Copy each child element into the link title.
      // Create a reference in case the content has not yet been generated.
      foreach (Element::children($element) as $group_child) {
        $element['#title'][$group_child] = &$element[$group_child];
      }
    }
  }

  /**
   * Get the entity object from the rendering object.
   *
   * @param array $rendering_object
   *   The group render array.  As passed to preRender().
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   Either the entity, or NULL if not found.
   */
  protected function getEntity(array $rendering_object) {
    // Get the entity key from the entity type.
    if (!isset($this->group->entity_type)) {
      return NULL;
    }
    $entity_key = '#' . $this->group->entity_type;

    if (!isset($rendering_object[$entity_key])) {
      // Some entity types store the key in an arbitrary name.
      // Check for the ones that we know of.
      switch ($this->group->entity_type) {
        case 'taxonomy_term':
          $entity_key = '#term';
          break;

        case 'user':
          $entity_key = '#account';
          break;

        // Otherwise just search for #entity.
        default:
          $entity_key = '#entity';
      }
    }

    if (isset($rendering_object[$entity_key]) && $rendering_object[$entity_key] instanceof ContentEntityInterface) {
      return $rendering_object[$entity_key];
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getClasses() {
    $classes = array('field-group-link');
    $classes = array_merge($classes, parent::getClasses());
    return $classes;
  }

  /**
   * Retrieve the url object from a field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The object of the current entity.
   *
   * @return \Drupal\Core\Url|null
   *   Either a valid Url object, or NULL.
   */
  protected function getUrlFromField(EntityInterface $entity) {
    $url = NULL;

    $field_name = $this->getSetting('target');

    // Make sure the field (still) exists. Also filters out _none.
    if ($field_definition = $entity->getFieldDefinition($field_name)) {
      $field_value = $entity->get($field_name)->getValue();

      if (!empty($field_value[0])) {
        switch ($field_definition->getType()) {
          case 'link':
            $options = $field_value[0]['options'] ?? [];
            $url = Url::fromUri($field_value[0]['uri'], $options);
            break;

          case 'image':
          case 'file':
            $file = File::load($field_value[0]['target_id']);
            if ($file) {
              $url = Url::fromUri($file->createFileUrl(FALSE));
            }
            break;

          case 'entity_reference':
            $target_entity = $this->entityTypeManager->getStorage($field_definition->getSetting('target_type'))->load($field_value[0]['target_id']);
            if ($target_entity instanceof EntityInterface) {
              $url = $target_entity->toUrl();
            }
            break;
        }
      }
    }

    return $url;
  }

}
