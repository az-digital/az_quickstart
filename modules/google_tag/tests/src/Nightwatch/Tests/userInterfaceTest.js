module.exports = {
  '@tags': ['google_tag'],
  before(browser) {
    browser
      .drupalInstall({
        setupFile: `${__dirname}/../TestNoConfigSiteInstallTestScript.php`,
      })
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: ['administer google_tag_container'],
      })
      .drupalLogin({ name: 'user', password: '123' });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'can be configured with one measurement ID': (browser) => {
    browser
      .drupalRelativeURL('/admin/config/services/google-tag')
      .waitForElementVisible('body', 1000)
      .setValue('[name="accounts[0][value]"]', 'G-XXXXXX')
      .execute(() =>
        document.querySelector('[name="accounts[0][value]"]').blur(),
      )
      .pause(75)
      .click('[data-drupal-selector="edit-submit"]')
      .assert.containsText('body', 'The configuration options have been saved.')
      .assert.googleTagExists()
      .assert.dataLayerContains(['config', 'G-XXXXXX'])
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'can be configured with multiple measurement IDs': (browser) => {
    browser
      .drupalRelativeURL('/admin/config/services/google-tag')
      .waitForElementVisible('body', 1000)
      .click('[value="Add another ID"]')
      .waitForElementVisible('[name="accounts[1][value]"]', 1000)
      .setValue('[name="accounts[1][value]"]', 'UA-XXXXXX')
      .execute(() =>
        document.querySelector('[name="accounts[1][value]"]').blur(),
      )
      .pause(75)
      .click('[data-drupal-selector="edit-submit"]')
      .assert.containsText('body', 'The configuration options have been saved.')
      .assert.googleTagExists()
      .assert.dataLayerContains(['config', 'G-XXXXXX'])
      .assert.dataLayerContains(['config', 'UA-XXXXXX'])
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'can configure conditions': (browser) => {
    browser
      .drupalRelativeURL('/admin/config/services/google-tag')
      .waitForElementVisible('body', 1000)
      .click('[href="#edit-conditions-user-role"]')
      .click('[name="conditions[user_role][roles][anonymous]"]')
      .click('[data-drupal-selector="edit-submit"]')
      .assert.containsText('body', 'The configuration options have been saved.')
      .assert.not.googleTagExists()
      .assert.not.dataLayerContains(['config', 'G-XXXXXX'])
      .assert.not.dataLayerContains(['config', 'UA-XXXXXX'])
      .drupalRelativeURL('/user/logout')
      .click('[data-drupal-selector="edit-submit"]')
      .drupalRelativeURL('/test-page')
      .assert.googleTagExists()
      .assert.dataLayerContains(['config', 'G-XXXXXX'])
      .assert.dataLayerContains(['config', 'UA-XXXXXX'])
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
