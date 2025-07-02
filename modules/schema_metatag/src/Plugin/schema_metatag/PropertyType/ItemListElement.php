<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Url;
use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;
use Drupal\views\Views;

/**
 * Provides a plugin for the 'ItemListElement' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "item_list_element",
 *   label = @Translation("ItemListElement"),
 *   tree_parent = {
 *     "ItemListElement",
 *   },
 *   tree_depth = -1,
 *   property_type = "ItemListElement",
 *   sub_properties = {},
 * )
 */
class ItemListElement extends PropertyTypeBase {

  /**
   * {@inheritdoc}
   */
  public function form($input_values) {
    $value = $input_values['value'];
    $form = [
      '#type' => 'textfield',
      '#title' => $input_values['title'],
      '#description' => $input_values['description'],
      '#default_value' => !empty($value) ? $value : '',
      '#maxlength' => 255,
    ];
    $form['#description']  = $this->t('To create a list, provide a token for a multiple value field, or a comma-separated list of values.');
    $form['#description'] .= $this->t("OR Provide the machine name of the view, and the machine name of the display, separated by a colon, i.e. 'view_name:display_id'. This will create a <a href=':url'>Summary View</a> list, which assumes each list item contains the url to a view page for the entity. The view rows should contain content (like teaser views) rather than fields for this to work correctly.", [':url' => 'https://developers.google.com/search/docs/guides/mark-up-listings']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function outputValue($input_value) {
    $items = [];
    $values = $this->getItems($input_value);
    if (!empty($values) && is_array($values)) {
      foreach ($values as $key => $value) {
        if (is_array($value)) {
          // Maps to Google all-in-one page view.
          if (array_key_exists('@type', $value)) {
            $items[] = [
              '@type' => 'ListItem',
              'position' => $key,
              'item' => $value,
            ];
          }
          // Maps to Google summary list view.
          elseif (array_key_exists('url', $value)) {
            $items[] = [
              '@type' => 'ListItem',
              'position' => $key,
              'url' => $value['url'],
            ];
          }
          // Maps to breadcrumb list.
          elseif (array_key_exists('name', $value) && array_key_exists('item', $value)) {
            $items[] = [
              '@type' => 'ListItem',
              'position' => $key,
              'name' => $value['name'],
              'item' => $value['item'],
            ];
          }
        }
        // Alternative simple list.
        else {
          $items[] = $value;
        }
      }
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems($input_value) {

    // A simple array of values.
    $list = $this->schemaMetatagManager()->explode($input_value);
    if (is_array($list)) {
      return $list;
    }

    // A string that is not a view/display.
    elseif (strpos(':', $input_value) !== FALSE) {
      return $input_value;
    }

    // A view and display in the format view_name:display_name.
    else {

      $values = [];
      $args = explode(':', $input_value);
      if (empty($args)) {
        return $values;
      }

      // Load the requested view.
      $view_id = array_shift($args);
      $view = Views::getView($view_id);

      // Set the display.
      if (count($args) > 0) {
        $display_id = array_shift($args);
        $view->setDisplay($display_id);
      }
      else {
        $view->initDisplay();
      }

      // See if the page's arguments should be passed to the view.
      if (count($args) == 1 && $args[0] == '{{args}}') {
        $view_path = explode("/", $view->getPath());
        $current_url = Url::fromRoute('<current>');
        $query_args = explode("/", substr($current_url->toString(), 1));

        $args = [];
        foreach ($query_args as $index => $arg) {
          if (in_array($arg, $view_path)) {
            unset($query_args[$index]);
          }
        }
        if (!empty($query_args)) {
          $args = array_values($query_args);
        }
      }

      // Allow modules to alter the arguments passed to the view.
      // @phpstan-ignore-next-line as its used on purpose.
      \Drupal::moduleHandler()->alter('schema_item_list_views_args', $args);

      if (!empty($args)) {
        $view->setArguments($args);
      }

      $view->preExecute();
      $view->execute();
      // Get the view results.
      $key = 1;
      foreach ($view->result as $item) {
        // If this is a display that does not provide an entity in the result,
        // there is really nothing more to do.
        $entity = static::getEntityFromRow($item);
        if (!$entity) {
          return '';
        }
        // Get the absolute path to this entity.
        $url = $entity->toUrl()->setAbsolute()->toString();
        $values[$key] = [
          '@id' => $url,
          'name' => $entity->label(),
          'url' => $url,
        ];
        $key++;
      }
    }
    return $values;
  }

  /**
   * Tries to retrieve an entity from a Views row.
   *
   * @param object $row
   *   The Views row.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity or NULL.
   */
  protected static function getEntityFromRow($row) {
    if (!empty($row->_entity)) {
      return $row->_entity;
    }

    if (isset($row->_object) && $row->_object instanceof EntityAdapter) {
      return $row->_object->getValue();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function testValue($type = '') {
    return 'first,second';
  }

}
