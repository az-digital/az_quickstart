<?php

namespace Drupal\Tests\az_mail\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test to ensure the metrics module grabs the desired data for a test site.
 *
 * @group az_metrics
 */
class MailModuleTest extends BrowserTestBase{

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
        'az_mail',
        'az_core'
        ];

    public function testMail(){

        $user = $this->drupalCreateUser(['administer quickstart configuration']);
        
        $this->drupalLogin($user);
        
        $assert = $this->assertSession();
        
        $ret = $this->drupalGet('');
        
        $this->assertSession()->statusCodeEquals(200);
        
        
        $message = "Hello!";

        // Call function from az_mail.module
        az_mail_mail_alter($message);

    }

}
