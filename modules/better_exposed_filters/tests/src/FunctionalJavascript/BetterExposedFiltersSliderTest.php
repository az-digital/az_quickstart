<?php

namespace Drupal\Tests\better_exposed_filters\FunctionalJavascript;

/**
 * Tests the basic AJAX functionality of BEF exposed forms.
 *
 * @group better_exposed_filters
 */
class BetterExposedFiltersSliderTest extends BetterExposedFiltersTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a few test nodes.
    $this->createNode([
      'title' => 'Page One',
      'field_bef_price' => '10',
      'type' => 'bef_test',
    ]);
    $this->createNode([
      'title' => 'Page Two',
      'field_bef_price' => '75',
      'type' => 'bef_test',
    ]);
  }

  /**
   * Tests a single slider field.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testBefSliderSingle(): void {
    $this->drupalGet('/bef-test-slider-single');

    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Verify input for slider is present.
    $session->fieldExists('field_bef_price_value');
    $session->fieldValueEquals('field_bef_price_value', '0');
    // Verify the slider from noUiSlider.
    $session->elementExists('css', '.bef-slider');
    $session->elementAttributeContains('css', '.bef-slider .noUi-handle', 'aria-valuenow', '0.0');

    // Update the input field to trigger the slider.
    $sliderField = $page->find('css', '#edit-field-bef-price-value');
    $sliderField->setValue('50');

    $page->find('css', '#edit-items-per-page')->focus();

    // Verify the slider updated.
    $session->elementAttributeContains('css', '.bef-slider .noUi-handle', 'aria-valuenow', '50.0');

    $this->submitForm([], 'Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextNotContains('Page One');
    $this->assertSession()->pageTextContains('Page Two');
  }

  /**
   * Tests an in between slider.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testBefSliderBetween(): void {
    $this->drupalGet('/bef-test-slider-between');

    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Verify input for slider is present.
    $session->fieldExists('field_bef_price_value[min]');
    $session->fieldExists('field_bef_price_value[max]');
    $session->fieldValueEquals('field_bef_price_value[min]', '0');
    $session->fieldValueEquals('field_bef_price_value[max]', '100');
    // Verify the slider from noUiSlider.
    $session->elementExists('css', '.bef-slider');
    $session->elementAttributeContains('css', '.bef-slider .noUi-handle.noUi-handle-lower', 'aria-valuenow', '0.0');
    $session->elementAttributeContains('css', '.bef-slider .noUi-handle.noUi-handle-upper', 'aria-valuenow', '100.0');

    // Update the input field to trigger the slider.
    $sliderFieldMin = $page->find('css', '#edit-field-bef-price-value-min');
    $sliderFieldMin->setValue('5');

    $sliderFieldMax = $page->find('css', '#edit-field-bef-price-value-max');
    $sliderFieldMax->setValue('15');

    $page->find('css', '#edit-items-per-page')->focus();

    // Verify the slider updated.
    $session->elementAttributeContains('css', '.bef-slider .noUi-handle.noUi-handle-lower', 'aria-valuenow', '5.0');
    $session->elementAttributeContains('css', '.bef-slider .noUi-handle.noUi-handle-upper', 'aria-valuenow', '15.0');

    $this->submitForm([], 'Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Page One');
    $this->assertSession()->pageTextNotContains('Page Two');
  }

}
