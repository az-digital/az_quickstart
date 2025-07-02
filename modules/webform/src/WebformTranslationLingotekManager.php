<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Defines a class to translate webform Lingotek integration.
 */
class WebformTranslationLingotekManager implements WebformTranslationLingotekManagerInterface {

  /**
   * The webform translation manager.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $translationManager;

  /**
   * Constructs a WebformTranslationLingotekManager object.
   *
   * @param \Drupal\webform\WebformTranslationManagerInterface $translation_manager
   *   The webform translation manager.
   */
  public function __construct(WebformTranslationManagerInterface $translation_manager) {
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function configEntityDocumentUpload(array &$source_data, ConfigEntityInterface &$entity, &$url) {
    switch ($entity->getEntityTypeId()) {
      case 'field_config':
        // Convert webform default data YAML string to an associative array.
        /** @var \Drupal\field\Entity\FieldConfig $entity */
        if ($entity->getFieldStorageDefinition()->getType() === 'webform') {
          foreach ($source_data as &$field_settings) {
            foreach ($field_settings as $setting_name => $setting_value) {
              if (preg_match('/\.default_data$/', $setting_name)) {
                $field_settings[$setting_name] = Yaml::decode($field_settings[$setting_name]);
              }
            }
            $this->encodeTokens($field_settings);
          }
        }
        break;

      case 'webform';
        // Replace elements with just the translatable properties
        // (i.e. #title, #description, #options, etc…) so that Lingotek's
        // translation services can correctly translate each element.
        $translation_elements = $this->translationManager->getTranslationElements($entity, $entity->language()->getId());
        $source_data['elements'] = $translation_elements;

        $this->encodeTokens($source_data);
        break;

      case 'webform_image_select_images';
        // Convert images YAML string to an associative array.
        $source_data['images'] = Yaml::decode($source_data['images']);
        break;

      case 'webform_options';
      case 'webform_options_custom';
        // Convert options YAML string to an associative array.
        $options = Yaml::decode($source_data['options']);

        // Extract optgroups from the options and append them as '_optgroups_'
        // to the options so that the optgroups can be translated.
        $optgroups = [];
        foreach ($options as $option_value => $option_text) {
          if (is_array($option_text)) {
            $optgroups[$option_value] = $option_value;
          }
        }
        if ($optgroups) {
          $options['_optgroups_'] = $optgroups;
        }

        // Update source data's options.
        $source_data['options'] = $options;
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configEntityTranslationPresave(ConfigEntityInterface &$translation, $langcode, &$data) {
    switch ($translation->getEntityTypeId()) {
      case 'field_config':
        // Convert webform default data associative array back to YAML string.
        /** @var \Drupal\field\Entity\FieldConfig $translation */
        if ($translation->getFieldStorageDefinition()->getType() === 'webform') {
          foreach ($data as &$field_settings) {
            $this->encodeTokens($field_settings);
            foreach ($field_settings as $setting_name => $setting_value) {
              if (preg_match('/\.default_data$/', $setting_name)) {
                $field_settings[$setting_name] = $field_settings[$setting_name] ? Yaml::encode($field_settings[$setting_name]) : '';
              }
            }
          }
        }
        break;

      case 'webform';
        $this->decodeTokens($data);

        /** @var \Drupal\webform\WebformInterface $translation */
        $translation->setElements($data['elements']);
        $data['elements'] = Yaml::encode($data['elements']);
        break;

      case 'webform_image_select_images';
        /** @var \Drupal\webform_image_select\WebformImageSelectImagesInterface $translation */
        // Convert images associative array back to YAML string.
        $translation->setImages($data['images']);
        $data['images'] = Yaml::encode($data['images']);
        break;

      case 'webform_options';
      case 'webform_options_custom';
        $options = $data['options'];
        // If '_optgroups_' are defined we need to translate the optgroups.
        if (isset($options['_optgroups_'])) {
          // Get optgroup from options.
          $optgroups = $options['_optgroups_'];
          unset($options['_optgroups_']);

          // Build translated optgroup options.
          $optgroups_options = [];
          foreach ($options as $option_value => $option_text) {
            if (is_array($option_text)) {
              $optgroups_options[$optgroups[$option_value]] = $option_text;
            }
            else {
              $optgroup_options[$option_value] = $option_text;
            }
          }
          // Replace options with optgroup options.
          $options = $optgroups_options;
        }

        /** @var \Drupal\webform\WebformOptionsInterface $translation */
        // Convert options associative array back to YAML string.
        $translation->setOptions($options);
        $data['options'] = Yaml::encode($options);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configObjectDocumentUpload(array &$data, $config_name) {
    if ($config_name !== 'webform.settings') {
      return;
    }

    $data['webform.settings']['test.types'] = Yaml::decode($data['webform.settings']['test.types']);
    $data['webform.settings']['test.names'] = Yaml::decode($data['webform.settings']['test.names']);

    $this->encodeTokens($data);
  }

  /**
   * {@inheritdoc}
   */
  public function configObjectTranslationPresave(array &$data, $config_name) {
    if ($config_name !== 'webform.settings') {
      return;
    }

    $this->decodeTokens($data);

    $data['webform.settings']['test.types'] = Yaml::encode($data['webform.settings']['test.types']);
    $data['webform.settings']['test.names'] = Yaml::encode($data['webform.settings']['test.names']);
  }

  /* ************************************************************************** */
  // Lingotek decode/encode token functions.
  /* ************************************************************************** */

  /**
   * Encode all tokens so that they won't be translated.
   *
   * @param array $data
   *   An array of data.
   */
  protected function encodeTokens(array &$data) {
    $yaml = Yaml::encode($data);
    $yaml = preg_replace_callback(
      '/\[([a-z][^]]+)\]/',
      function ($matches) {
        // Encode all token characters to HTML entities.
        // @see https://stackoverflow.com/questions/6720826/php-convert-all-characters-to-html-entities.
        $replacement = mb_encode_numericentity($matches[1], [0x000000, 0x10ffff, 0, 0xffffff], 'UTF-8');
        return "[$replacement]";
      },
      $yaml
    );
    $data = Yaml::decode($yaml);
  }

  /**
   * Decode all tokens after string have been translated.
   *
   * @param array $data
   *   An array of data.
   */
  protected function decodeTokens(array &$data) {
    $yaml = Yaml::encode($data);
    $yaml = preg_replace_callback(
      '/\[([^]]+?)\]/',
      function ($matches) {
        // Decode token HTML entities to characters.
        // @see https://stackoverflow.com/questions/6720826/php-convert-all-characters-to-html-entities.
        $token = mb_decode_numericentity($matches[1], [0x000000, 0x10ffff, 0, 0xffffff], 'UTF-8');
        return "[$token]";
      },
      $yaml
    );
    $data = Yaml::decode($yaml);
  }

}
