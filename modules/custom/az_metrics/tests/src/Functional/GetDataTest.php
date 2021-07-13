<?php

namespace Drupal\Tests\az_metrics\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test to ensure the metrics module grabs the desired data for a test site.
 *
 * @group az_metrics
 */
class GetDataTest extends BrowserTestBase{

    /**
    * The profile to install as a basis for testing.
    *
    * @var string
    */
    protected $profile = 'az_quickstart';

    /**
     * @var bool
     */
    protected $strictConfigSchema = FALSE;

    /**
     * @var string
     */
    protected $defaultTheme = 'seven';

    /**
     * Modules to enable.
     *
     * @var array
    */
    protected static $modules = [
        'az_metrics',
        'az_core'
        ];

    public function testGetData(){

        $user = $this->drupalCreateUser(['administer quickstart configuration']);
        
        $this->drupalLogin($user);
        
        $assert = $this->assertSession();
        
        $ret = $this->drupalGet('');
        
        $this->assertSession()->statusCodeEquals(200);
        
        // Call function from az_metrics.module
        $data = az_metrics_data();

        $empty = 0;

        // Checks the domain array generated
        $domainListSize = count($data['domains']);

        $this->assertGreaterThan($empty, $domainListSize, "The domains list is empty!");

        // Checks that there is a mail address generated
        $mailSize = strlen($data['mail']);

        $this->assertGreaterThan($empty, $mailSize, "The uuid is empty!");

        // Checks that there is a name generated
        $nameSize = strlen($data['name']);

        $this->assertGreaterThan($empty, $nameSize, "The uuid is empty!");

        // Checks that there is a uuid generated
        $uuidSize = strlen($data['uuid']);

        $this->assertGreaterThan($empty, $uuidSize, "The uuid is empty!");

    }

}
