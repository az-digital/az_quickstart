module.exports = {
  '@tags': ['google_tag'],
  before(browser) {
    browser.drupalInstall({
      setupFile: `${__dirname}/../TestSiteInstallTestScript.php`,
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'gtag exists': (browser) => {
    browser
      .drupalRelativeURL('/test-page')
      .assert.googleTagExists()
      .assert.googleTagValueEquals('tagId', 'GT-XXXXXX')
      .assert.googleTagValueEquals('otherIds', [
        'G-XXXXXX',
        'AW-XXXXXX',
        'DC-XXXXXX',
        'UA-XXXXXX',
      ])
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'gtag events bubbled': (browser) => {
    browser
      .drupalRelativeURL('/test-page')
      .assert.googleTagExists()
      .assert.not.googleTagValueEquals('events', [])
      .assert.googleTagValueEquals('events', [
        {
          data: {
            route_name: 'test_page_test.test_page',
          },
          name: 'route_name',
        },
      ])
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'dataLayer contains events': (browser) => {
    browser
      .drupalRelativeURL('/test-page')
      .assert.googleTagExists()
      .assert.not.googleTagValueEquals('events', [])
      .assert.dataLayerContains([
        'config',
        'GT-XXXXXX',
        {
          foo: 6,
          langcode: 'en',
        },
      ])
      .assert.dataLayerContains(['config', 'G-XXXXXX'])
      .assert.dataLayerContains(['config', 'AW-XXXXXX'])
      .assert.dataLayerContains(['config', 'DC-XXXXXX'])
      .assert.dataLayerContains(['config', 'UA-XXXXXX'])
      .assert.dataLayerContains(['set', 'developer_id.dMDhkMT', true])
      .assert.dataLayerContains([
        'event',
        'route_name',
        { route_name: 'test_page_test.test_page' },
      ])
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'custom metrics and dimensions set': (browser) => {
    browser
      .drupalRelativeURL('/test-page')
      .assert.googleTagExists()
      .assert.not.googleTagValueEquals('dimensions_metrics', [])
      .assert.dataLayerContains([
        'config',
        'GT-XXXXXX',
        {
          foo: 6,
          langcode: 'en',
        },
      ]);
  },
};
