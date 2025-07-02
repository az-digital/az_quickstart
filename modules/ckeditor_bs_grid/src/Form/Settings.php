<?php

namespace Drupal\ckeditor_bs_grid\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

/**
 * Configuration for CKEditor BS Grid.
 */
class Settings extends ConfigFormBase {

  // Config item.
  const CONFIG_NAME = 'ckeditor_bs_grid.settings';

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'ckeditor_bs_grid.settings';
  }

  /**
   * Helper to grab existing BS breakpoints.
   *
   * @todo make these configurable in the case of custom.
   *
   * @return array[]
   *   The available Breakpoints.
   */
  protected function getBreakpoints() {
    $breakpoints = [
      'xs' => ['bs_label' => $this->t('Extra Small (xs)'), 'prefix' => 'none'],
      'sm' => ['bs_label' => $this->t('Small (sm)'), 'prefix' => 'sm'],
      'md' => ['bs_label' => $this->t('Medium (md)'), 'prefix' => 'md'],
      'lg' => ['bs_label' => $this->t('Large (lg)'), 'prefix' => 'lg'],
      'xl' => ['bs_label' => $this->t('Extra large (xl)'), 'prefix' => 'xl'],
      'xxl' => [
        'bs_label' => $this->t('Extra extra large (xxl)'),
        'prefix' => 'xxl',
      ],
    ];
    return $breakpoints;
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $breakpoints = $this->getBreakpoints();
    $config = $this->configFactory()->get(self::CONFIG_NAME)->get('breakpoints');

    $message = $this->t(
      'Breakpoints and Number of columns can be enabled/disabled per text format in the @link.',
      ['@link' => Link::createFromRoute('editor settings page', 'filter.admin_overview')->toString()]);
    $form['prefix'] = [
      '#type' => 'markup',
      '#markup' => Markup::create("<div class='messages messages--warning'>" . $message . "</div>"),
      '#weight' => -100,
    ];

    // Column Options. @todo make this configurable.
    $cols = [];
    for ($i = 1; $i <= 12; $i++) {
      $cols[$i] = $i;
    }

    $form['#tree'] = TRUE;

    $defaultLayoutOptions = [
      'none' => $this->t('None'),
      'order' => $this->t('By Sort Order'),
    ];

    $group_class = 'group-order-weight';
    foreach ($breakpoints as $break => $data) {

      $form[$break] = [
        '#title' => $data['bs_label'],
        '#type' => 'details',
        '#open' => FALSE,
      ];

      $form[$break]['label'] = [
        '#title' => $this->t('Label'),
        '#type' => 'textfield',
        '#default_value' => $config[$break]['label'] ?? $data['bs_label'],
        '#required' => TRUE,
      ];

      $form[$break]['prefix'] = [
        '#title' => $this->t('Prefix'),
        '#type' => 'textfield',
        '#default_value' => $data['prefix'],
        '#disabled' => TRUE,
        '#required' => TRUE,
      ];

      $form[$break]['columns'] = [
        '#title' => $this->t('Available Column Layouts'),
        '#type' => 'details',
        '#description' => $this->t('In the format of key|value, where the key is the attribute to add to each column, separated by a comma. Special tags are "auto" and "equal"'),
      ];

      $options = [
        'equal' => $this->t('Equal'),
        'auto' => $this->t('Auto'),
      ] + $cols;

      // For now this is static to 12.
      foreach ($cols as $col) {
        $default_layout = 'none';
        if (!empty($config[$break]['columns'][$col]['default_layout'])) {
          $default_layout = $config[$break]['columns'][$col]['default_layout'];
        }
        $form[$break]['columns'][$col] = [
          '#title' => $this->t('@num Column Layout (default: @default)', [
            '@num' => $col,
            '@default' => $defaultLayoutOptions[$default_layout],
          ]),
          '#type' => 'details',
          '#prefix' => '<div id="fieldset-wrapper-' . $break . '-' . $col . '">',
          '#suffix' => '</div>',
          '#open' => FALSE,
        ];

        $form[$break]['columns'][$col]['default_layout'] = [
          '#title' => $this->t('Default Layout Selection'),
          '#description' => $this->t('Select "By Sort Order" to make the top option the default for this layout.'),
          '#type' => 'select',
          '#options' => $defaultLayoutOptions,
          '#default_value' => $default_layout,
        ];

        // Build table.
        $form[$break]['columns'][$col]['layouts'] = [
          '#type' => 'table',
          '#caption' => $this->t('Available Layouts:'),
          '#header' => [
            $this->t('Label'),
            $this->t('Settings'),
            $this->t('Operations'),
            $this->t('Weight'),
          ],
          '#empty' => $this->t('No items.'),
          '#tableselect' => FALSE,
          '#tabledrag' => [
            [
              'action' => 'order',
              'relationship' => 'sibling',
              'group' => $group_class,
            ],
          ],
        ];

        $existing_layouts = $config[$break]['columns'][$col]['layouts'] ?? [];

        // Fix for BC.
        if (!is_array($existing_layouts)) {
          $existing_layouts = $config[$break]['columns'][$col]['layouts'] = [];
        }

        $current_num = count($existing_layouts);
        $num_layouts = $form_state->get('num_' . $break . '_' . $col);
        if ($num_layouts === NULL) {
          $form_state->set('num_' . $break . '_' . $col, $current_num);
          $num_layouts = $current_num;
        }

        // Add more.
        for ($i = $num_layouts - $current_num; $i > 0; $i--) {
          $existing_layouts[] = [
            'weight' => NULL,
            'label' => $this->t('New Layout'),
            'settings' => FALSE,
          ];
        }

        $option_count = 0;
        foreach ($existing_layouts as $vals) {

          $remove_key = 'remove_' . $break . '_' . $col . '_' . $option_count;
          if ($form_state->get($remove_key)) {
            $option_count++;
            continue;
          }

          $form[$break]['columns'][$col]['layouts'][$option_count]['#attributes']['class'][] = 'draggable';
          $form[$break]['columns'][$col]['layouts'][$option_count]['#weight'] = $vals['weight'] ?? 0;

          $form[$break]['columns'][$col]['layouts'][$option_count]['label'] = [
            '#title' => $this->t('Label'),
            '#type' => 'textfield',
            '#required' => TRUE,
            '#default_value' => $vals['label'],
          ];

          $form[$break]['columns'][$col]['layouts'][$option_count]['settings'] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['container-inline'],
            ],
          ];

          for ($j = 1; $j <= $col; $j++) {
            $form[$break]['columns'][$col]['layouts'][$option_count]['settings']['col-' . $j] = [
              '#title' => $j,
              '#type' => 'select',
              '#options' => $options,
              '#default_value' => $vals['settings']['col-' . $j] ?? 'equal',
            ];
          }

          // If there is more than one name, add the remove button.
          if ($num_layouts > 1) {
            $form[$break]['columns'][$col]['layouts'][$option_count]['operations'] = [
              '#type' => 'submit',
              '#value' => $this->t('Remove'),
              '#submit' => [
                '::removeCallback',
              ],
              '#ajax' => [
                'callback' => '::ajaxCallback',
                'wrapper' => 'fieldset-wrapper-' . $break . '-' . $col,
              ],
              '#name' => 'remove-' . $break . '-' . $col . '-' . $option_count,
            ];
          }
          else {
            $form[$break]['columns'][$col]['layouts'][$option_count]['operations'] = [
              '#markup' => '',
            ];
          }

          // Weight col.
          $form[$break]['columns'][$col]['layouts'][$option_count]['weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight'),
            '#title_display' => 'invisible',
            '#default_value' => $vals['weight'] ?? 0,
            '#attributes' => ['class' => [$group_class]],
          ];
          $option_count++;
        }

        $form[$break]['columns'][$col]['actions'] = [
          '#type' => 'actions',
        ];
        $form[$break]['columns'][$col]['add_name'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add More'),
          '#submit' => [
            '::addOne',
          ],
          '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'fieldset-wrapper-' . $break . '-' . $col,
          ],
          '#name' => 'add-' . $break . '-' . $col,
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->cleanValues()->getValues();
    unset($values['actions']);
    $this->configFactory()
      ->getEditable(self::CONFIG_NAME)
      ->set('breakpoints', $values)
      ->save();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $this->getTriggerKey($form_state);
    $form[$trigger['break']]['columns'][$trigger['col']]['#open'] = TRUE;
    return $form[$trigger['break']]['columns'][$trigger['col']];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $trigger = $this->getTriggerKey($form_state);
    $value_key = 'num_' . $trigger['break'] . '_' . $trigger['col'];
    $num_layouts = $form_state->get($value_key);
    $form_state->set($value_key, $num_layouts + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $this->getTriggerKey($form_state);
    $value_key = 'remove_' . $trigger['break'] . '_' . $trigger['col'] . '_' . $trigger['option'];
    $form_state->set($value_key, 1);
    $form_state->setRebuild();
  }

  /**
   * Helper to find the name of the trigger item.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The keys to look for.
   */
  protected function getTriggerKey(FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parts = explode('-', $trigger['#name']);
    return [
      'break' => $parts[1],
      'col' => $parts[2],
      'option' => $parts[3] ?? NULL,
    ];
  }

}
