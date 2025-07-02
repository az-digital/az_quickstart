<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\Kernel;

use Drupal\Tests\ckeditor5\Kernel\ValidatorsTest as CKEditor5CoreValidatorsTest;

/**
 * @covers \Drupal\linkit\Plugin\CKEditor5Plugin\Linkit::validChoices
 * @covers \Drupal\linkit\Plugin\CKEditor5Plugin\Linkit::requireProfileIfEnabled
 * @covers linkit.schema.yml
 *
 * @group linkit
 */
abstract class AbstractValidatorsTest extends CKEditor5CoreValidatorsTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'linkit',
    // @see config/optional/linkit.linkit_profile.default.yml
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // @see config/optional/linkit.linkit_profile.default.yml
    $this->installConfig(['linkit']);
  }

}

if (version_compare(\Drupal::VERSION, '10.3', '>')) {
  /**
   * {@inheritdoc}
   *
   * @group linkit
   */
  class ValidatorsTest extends AbstractValidatorsTest {

    /**
     * {@inheritdoc}
     */
    public static function provider(): array {
      $linkit_test_cases_toolbar_settings = ['items' => ['link']];

      $data = [];
      $data['VALID: installing the linkit module without configuring the existing text editors'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [],
        ],
        'expected_violations' => [],
      ];
      $data['INVALID: linkit — invalid manually created configuration'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [
            'linkit_extension' => [
              'linkit_enabled' => 'no',
            ],
          ],
        ],
        'expected_violations' => [
          'settings.plugins.linkit_extension.linkit_enabled' => 'This value should be of the correct primitive type.',
        ],
      ];
      $data['VALID: linkit off'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [
            'linkit_extension' => [
              'linkit_enabled' => FALSE,
            ],
          ],
        ],
        'expected_violations' => [],
      ];
      $data['VALID: linkit off, profile selected'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [
            'linkit_extension' => [
              'linkit_enabled' => TRUE,
              'linkit_profile' => 'default',
            ],
          ],
        ],
        'expected_violations' => [],
      ];
      $data['INVALID: linkit on, no profile selected'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [
            'linkit_extension' => [
              'linkit_enabled' => TRUE,
            ],
          ],
        ],
        'expected_violations' => [
          'settings.plugins.linkit_extension.linkit_profile' => 'Linkit is enabled, please select the Linkit profile you wish to use.',
        ],
      ];
      $data['INVALID: linkit on, non-existent profile selected'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [
            'linkit_extension' => [
              'linkit_enabled' => TRUE,
              'linkit_profile' => 'nonexistent',
            ],
          ],
        ],
        'expected_violations' => [
          'settings.plugins.linkit_extension.linkit_profile' => 'The value you selected is not a valid choice.',
        ],
      ];
      $data['VALID: linkit on, existing profile selected'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [
            'linkit_extension' => [
              'linkit_enabled' => TRUE,
              'linkit_profile' => 'default',
            ],
          ],
        ],
        'expected_violations' => [],
      ];
      return $data;
    }

  }
}
else {
  /**
   * {@inheritdoc}
   *
   * @group linkit
   */
  class ValidatorsTest extends AbstractValidatorsTest {

    /**
     * {@inheritdoc}
     */
    public function providerPair(): array {
      // Linkit is 100% independent of the text format, so no need for this test.
      return [];
    }


    /**
     * {@inheritdoc}
     */
    public function provider(): array {
      $linkit_test_cases_toolbar_settings = ['items' => ['link']];

      $data = [];
      $data['VALID: installing the linkit module without configuring the existing text editors'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [],
        ],
        'expected_violations' => [],
      ];
      $data['INVALID: linkit — invalid manually created configuration'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [
            'linkit_extension' => [
              'linkit_enabled' => 'no',
            ],
          ],
        ],
        'expected_violations' => [
          'settings.plugins.linkit_extension.linkit_enabled' => 'This value should be of the correct primitive type.',
        ],
      ];
      $data['VALID: linkit off'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [
            'linkit_extension' => [
              'linkit_enabled' => FALSE,
            ],
          ],
        ],
        'expected_violations' => [],
      ];
      $data['VALID: linkit off, profile selected'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [
            'linkit_extension' => [
              'linkit_enabled' => TRUE,
              'linkit_profile' => 'default',
            ],
          ],
        ],
        'expected_violations' => [],
      ];
      $data['INVALID: linkit on, no profile selected'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [
            'linkit_extension' => [
              'linkit_enabled' => TRUE,
            ],
          ],
        ],
        'expected_violations' => [
          'settings.plugins.linkit_extension.linkit_profile' => 'Linkit is enabled, please select the Linkit profile you wish to use.',
        ],
      ];
      $data['INVALID: linkit on, non-existent profile selected'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [
            'linkit_extension' => [
              'linkit_enabled' => TRUE,
              'linkit_profile' => 'nonexistent',
            ],
          ],
        ],
        'expected_violations' => [
          'settings.plugins.linkit_extension.linkit_profile' => 'The value you selected is not a valid choice.',
        ],
      ];
      $data['VALID: linkit on, existing profile selected'] = [
        'ckeditor5_settings' => [
          'toolbar' => $linkit_test_cases_toolbar_settings,
          'plugins' => [
            'linkit_extension' => [
              'linkit_enabled' => TRUE,
              'linkit_profile' => 'default',
            ],
          ],
        ],
        'expected_violations' => [],
      ];
      return $data;
    }

  }
}
