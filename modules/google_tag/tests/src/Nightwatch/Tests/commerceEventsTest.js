module.exports = {
  '@tags': ['google_tag'],
  before(browser) {
    browser.drupalInstall({
      setupFile: `${__dirname}/../TestCommerceSiteInstallTestScript.php`,
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'view_item is sent': (browser) => {
    browser
      .drupalRelativeURL('/product/1')
      .assert.googleTagExists()
      .assert.dataLayerContains([
        'event',
        'view_item',
        {
          currency: 'USD',
          items: [
            {
              affiliation: 'FooBar Store',
              item_id: 'ABC123',
              item_name: 'FooBaz Widget - Blue',
            },
          ],
          value: '12.00',
        },
      ])
      .click(
        'select[name="purchased_entity[0][attributes][attribute_color]"] option[value="2"]',
      )
      .assert.dataLayerContains([
        'event',
        'view_item',
        {
          currency: 'USD',
          items: [
            {
              affiliation: 'FooBar Store',
              item_id: 'DEF456',
              item_name: 'FooBaz Widget - Red',
            },
          ],
          value: '12.00',
        },
      ])
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'add_to_cart is sent': (browser) => {
    browser
      .drupalRelativeURL('/product/1')
      .assert.googleTagExists()
      .click('input[value="Add to cart"]')
      .assert.dataLayerContains([
        'event',
        'add_to_cart',
        {
          currency: 'USD',
          items: [
            {
              affiliation: 'FooBar Store',
              discount: '0',
              item_id: 'ABC123',
              item_name: 'FooBaz Widget - Blue',
              price: '12.00',
              quantity: 1,
            },
          ],
          value: '12.00',
        },
      ])
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'checkout events': (browser) => {
    browser
      .drupalRelativeURL('/cart')
      .assert.googleTagExists()
      .click('input[value="Checkout"]')
      .assert.dataLayerContains([
        'event',
        'begin_checkout',
        {
          currency: 'USD',
          items: [
            {
              affiliation: 'FooBar Store',
              discount: '0.00',
              item_id: 'ABC123',
              item_name: 'FooBaz Widget - Blue',
              price: '12.00',
              quantity: 1,
            },
          ],
          value: '12.00',
        },
      ])
      .click('input[value="Continue as Guest"]')
      .setValue('input[name="contact_information[email]"]', 'baz@example.com')
      .setValue(
        'input[name="contact_information[email_confirm]"]',
        'baz@example.com',
      )
      .setValue(
        'input[name="billing_information[profile][address][0][address][given_name]"]',
        'Baz',
      )
      .setValue(
        'input[name="billing_information[profile][address][0][address][family_name]"]',
        'Bar',
      )
      .setValue(
        'input[name="billing_information[profile][address][0][address][address_line1]"]',
        '123 Main St',
      )
      .setValue(
        'input[name="billing_information[profile][address][0][address][locality]"]',
        'Kenosha',
      )
      .setValue(
        'select[name="billing_information[profile][address][0][address][administrative_area]"]',
        'WI',
      )
      .setValue(
        'input[name="billing_information[profile][address][0][address][postal_code]"]',
        '53140',
      )
      .click('input[value="Continue to review"]')
      .click('input[value="Complete checkout"]')
      .assert.dataLayerContains([
        'event',
        'purchase',
        {
          currency: 'USD',
          items: [
            {
              affiliation: 'FooBar Store',
              discount: '0',
              item_id: 'ABC123',
              item_name: 'FooBaz Widget - Blue',
              price: '12.00',
              quantity: 1,
            },
          ],
          shipping: '0',
          tax: '0',
          transaction_id: '1',
          value: '12.00',
        },
      ])
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
