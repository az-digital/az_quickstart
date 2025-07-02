<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Traits;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\flag\Entity\Flag;

/**
 * Trait for programmatically creating Flags.
 */
trait FlagCreateTrait {

  /**
   * Create a basic flag programmatically.
   *
   * Creates a flag with the given entity type, bundles, and link type without
   * using the admin UI. The flag's ID, label, flag and unflag text will be
   * random strings.
   *
   * @param string|null $entity_type
   *   (optional) The entity type of the flag to create. If omitted,
   *   assumes 'node'.
   * @param array $bundles
   *   (optional) An array of entity bundles to which the flag applies.
   *   If NULL, all bundles are assumed.
   * @param string|null $link_type
   *   (optional) The ID of the link type to use. If omitted, assumes 'reload'.
   *
   * @return \Drupal\flag\FlagInterface
   *   A new flag entity with the given criteria.
   */
  protected function createFlag($entity_type = 'node', array $bundles = [], $link_type = 'reload') {
    return $this->createFlagFromArray([
      'entity_type' => $entity_type,
      'bundles' => $bundles,
      'link_type' => $link_type,
      'flag_type' => $this->getFlagType($entity_type),
    ]);
  }

  /**
   * Create a global flag programmatically.
   *
   * Creates a flag with the given entity type, bundles, and link type without
   * using the admin UI. The flag's ID, label, flag and unflag text will be
   * random strings.
   *
   * @param string|null $entity_type
   *   (optional) The entity type of the flag to create. If omitted,
   *   assumes 'node'.
   * @param array $bundles
   *   (optional) An array of entity bundles to which the flag applies.
   *   If NULL, all bundles are assumed.
   * @param string|null $link_type
   *   (optional) The ID of the link type to use. If omitted, assumes 'reload'.
   *
   * @return \Drupal\flag\FlagInterface
   *   A new flag entity with the given criteria.
   */
  protected function createGlobalFlag($entity_type = 'node', array $bundles = [], $link_type = 'reload') {
    return $this->createFlagFromArray([
      'entity_type' => $entity_type,
      'bundles' => $bundles,
      'link_type' => $link_type,
      'global' => TRUE,
    ]);
  }

  /**
   * Creates a flag from an array.
   *
   * Sensible key values pairs will be inserted into the input array if not
   * provided.
   *
   * @param array $edit
   *   The edit array to pass to Flag::create().
   *
   * @return \Drupal\flag\FlagInterface
   *   A new flag entity with the given criteria.
   */
  protected function createFlagFromArray(array $edit) {

    $default = [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'entity_type' => 'node',
      'bundles' => array_keys(\Drupal::service('entity_type.bundle.info')->getBundleInfo('node')),
      'flag_short' => $this->randomHtmlString(),
      'unflag_short' => $this->randomHtmlString(),
      'unflag_denied_text' => $this->randomHtmlString(),
      'flag_long' => $this->randomHtmlString(16),
      'unflag_long' => $this->randomHtmlString(16),
      'flag_message' => $this->randomHtmlString(32),
      'unflag_message' => $this->randomHtmlString(32),
      'flag_type' => $this->getFlagType('node'),
      'link_type' => 'reload',
      'flagTypeConfig' => [
        'show_as_field' => TRUE,
        'show_on_form' => FALSE,
        'show_contextual_link' => FALSE,
      ],
      'linkTypeConfig' => [],
      'global' => FALSE,
    ];

    $link_type = array_key_exists('link_type', $edit) ? $edit['link_type'] : 'reload';

    // To keep this up-to-date see flag.schema.yml.
    switch ($link_type) {
      case 'comment':
        $default = array_merge($default, [
          'flagTypeConfig' => [
            'access_author' => $this->randomHtmlString(),
          ],
        ]);
        break;

      case 'confirm':
        $default = array_merge($default, [
          'linkTypeConfig' => [
            'flag_confirmation' => $this->randomHtmlString(),
            'unflag_confirmation' => $this->randomHtmlString(),
          ],
        ]);
        break;

      case 'field_entry':
        $default = array_merge($default, [
          'linkTypeConfig' => [
            'flag_confirmation' => $this->randomHtmlString(),
            'unflag_confirmation' => $this->randomHtmlString(),
            'edit_flagging' => $this->randomHtmlString(),
          ],
        ]);
        break;

      default:
        break;
    }

    foreach ($default as $key => $value) {
      if (empty($edit[$key])) {
        $edit[$key] = $value;
      }
    }

    // Create the flag programmatically.
    $flag = Flag::create($edit);

    // Save the flag.
    $flag->save();

    // Make sure that we actually did get a flag entity.
    $this->assertTrue($flag instanceof Flag);

    return $flag;
  }

  /**
   * Get a flag type plugin ID for the given entity.
   *
   * @param string $entity_type
   *   The entity type of the flag type plugin to get.
   *
   * @return string
   *   A string containing the flag type ID.
   */
  protected function getFlagType($entity_type) {
    $all_flag_types = $this->container->get('plugin.manager.flag.flagtype')->getDefinitions();

    // Search and return the flag type ID that matches our entity.
    foreach ($all_flag_types as $plugin_id => $plugin_def) {
      if ($plugin_def['entity_type'] == $entity_type) {
        return $plugin_id;
      }
    }

    // Return the generic entity flag type plugin ID.
    return 'entity';
  }

  /**
   * Generates an HTML-safe random string.
   *
   * To generate strings which can be located in FunctionalJavascript tests.
   * In tests using 'css' queries that use the 'contains()' selector we need to
   * remove all white space characters.
   *
   * @param int $length
   *   The length of the string to generate.
   *
   * @return string
   *   A random string of HTML-safe characters.
   */
  protected function randomHtmlString($length = 8) {
    // A safe string.
    $str = Html::decodeEntities(Xss::filter($this->randomString($length * 2), []));

    // Remove all whitespaces.
    $no_space = preg_replace('/\s+/', '', $str);

    // Remove all angle brackets.
    $no_brackets = preg_replace('/[<>]/', '_', $no_space);

    // Trim to the required length.
    return substr($no_brackets, 0, $length);
  }

}
