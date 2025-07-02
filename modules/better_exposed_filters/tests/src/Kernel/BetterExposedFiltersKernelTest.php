<?php

namespace Drupal\Tests\better_exposed_filters\Kernel;

use Drupal\views\Views;

/**
 * Tests the radio buttons/checkboxes filter widget (i.e. "bef").
 *
 * @group better_exposed_filters
 *
 * @see \Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\RadioButtons
 */
class BetterExposedFiltersKernelTest extends BetterExposedFiltersKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['bef_test'];

  /**
   * Tests hiding the submit button when auto-submit is enabled.
   */
  public function testHideSubmitButtonOnAutoSubmit() {
    $view = Views::getView('bef_test');
    $display = &$view->storage->getDisplay('default');

    // Enable auto-submit and hide auto-submit button.
    $this->setBetterExposedOptions($view, [
      'general' => [
        'autosubmit' => TRUE,
        'autosubmit_hide' => TRUE,
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);

    // Check our "submit" button is hidden.
    $actual = $this->xpath("//form//input[@type='submit'][contains(concat(' ',normalize-space(@class),' '),' js-hide ')]");
    $this->assertCount(1, $actual, 'Submit button was hidden successfully.');

    $view->destroy();
  }

  /**
   * Tests moving sorts, filters and pager options into secondary fieldset.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSecondaryOptions() {
    $view = Views::getView('bef_test');
    $display = &$view->storage->getDisplay('default');

    // Enable secondary options and set label.
    $this->setBetterExposedOptions($view, [
      'general' => [
        'allow_secondary' => TRUE,
        'secondary_label' => 'Secondary Options TEST',
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);

    // Assert our "secondary" options detail is hidden if no fields are placed.
    $actual = $this->xpath("//form//details[@data-drupal-selector='edit-secondary']");
    $this->assertCount(0, $actual, 'Secondary options are hidden because no fields were placed.');

    $view->destroy();

    // Move sort, pager and "field_bef_boolean" into secondary options.
    $view = Views::getView('bef_test');

    $this->setBetterExposedOptions($view, [
      'general' => [
        'allow_secondary' => TRUE,
        'secondary_label' => 'Secondary Options TEST',
      ],
      'sort' => [
        'plugin_id' => 'default',
        'advanced' => [
          'is_secondary' => TRUE,
        ],
      ],
      'pager' => [
        'plugin_id' => 'default',
        'advanced' => [
          'is_secondary' => TRUE,
        ],
      ],
      'filter' => [
        'field_bef_boolean_value' => [
          'plugin_id' => 'default',
          'advanced' => [
            'is_secondary' => TRUE,
          ],
        ],
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);

    // Assert our "secondary" options detail is visible.
    $actual = $this->xpath("//form//details[@data-drupal-selector='edit-secondary']");
    $this->assertCount(1, $actual, 'Secondary options is visible.');

    // Assert sort option was placed in secondary details.
    $actual = $this->xpath("//form//details[@data-drupal-selector='edit-secondary']//select[@name='sort_by']");
    $this->assertCount(1, $actual, 'Exposed sort was placed in secondary fieldset.');

    // Assert pager option was placed in secondary details.
    $actual = $this->xpath("//form//details[@data-drupal-selector='edit-secondary']//select[@name='items_per_page']");
    $this->assertCount(1, $actual, 'Exposed pager was placed in secondary fieldset.');

    // Assert filter option was placed in secondary details.
    $actual = $this->xpath("//form//details[@data-drupal-selector='edit-secondary']//select[@name='field_bef_boolean_value']");
    $this->assertCount(1, $actual, 'Exposed filter "field_bef_boolean" was placed in secondary fieldset.');

    $view->destroy();
  }

}
