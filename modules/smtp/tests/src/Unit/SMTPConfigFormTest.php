<?php

namespace Drupal\Tests\smtp\Unit;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\smtp\Form\SMTPConfigForm;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validate requirements for SMTPConfigForm.
 *
 * @group SMTP
 */
class SMTPConfigFormTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Test setup.
   */
  public function setup(): void {
    parent::setup();
    $this->mockConfigFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->mockConfig = $this->prophesize(Config::class);
    $this->mockConfigFactory->get('smtp.settings')->willReturn($this->mockConfig->reveal());
    $this->mockConfigFactory->getEditable('smtp.settings')->willReturn($this->mockConfig->reveal());

    $this->mockConfigSystemSite = $this->prophesize(Config::class);
    $this->mockConfigSystemSite->get('name')->willReturn('Site name');
    $this->mockConfigFactory->get('system.site')->willReturn($this->mockConfigSystemSite->reveal());

    $this->mockTypedConfigManager = $this->prophesize(TypedConfigManagerInterface::class);
    $this->mockMessenger = $this->prophesize(MessengerInterface::class);
    $this->mockEmailValidator = $this->prophesize(EmailValidatorInterface::class);
    $this->mockCurrentUser = $this->prophesize(AccountProxyInterface::class);
    $this->mockMailManager = $this->prophesize(MailManagerInterface::class);
    $this->mockModuleHandler = $this->prophesize(ModuleHandlerInterface::class);

    $mockContainer = $this->mockContainer = $this->prophesize(ContainerInterface::class);
    $mockContainer->get('config.factory')->willReturn($this->mockConfigFactory->reveal());
    $mockContainer->get('config.typed')->willReturn($this->mockTypedConfigManager->reveal());
    $mockContainer->get('messenger')->willReturn($this->mockMessenger->reveal());
    $mockContainer->get('email.validator')->willReturn($this->mockEmailValidator->reveal());
    $mockContainer->get('current_user')->willReturn($this->mockCurrentUser->reveal());
    $mockContainer->get('plugin.manager.mail')->willReturn($this->mockMailManager->reveal());
    $mockContainer->get('module_handler')->willReturn($this->mockModuleHandler->reveal());

    $mockStringTranslation = $this->prophesize(TranslationInterface::class);
    $mockStringTranslation->translate(Argument::any())->willReturnArgument(0);
    $mockStringTranslation->translate(Argument::any(), Argument::any())->willReturnArgument(0);
    $mockStringTranslation->translateString(Argument::any())->willReturn('.');
    $mockContainer->get('string_translation')->willReturn($mockStringTranslation->reveal());

    \Drupal::setContainer($this->mockContainer->reveal());
  }

  /**
   * Sets the default smtp config.
   */
  public function setDefaultConfig() {
    $this->mockConfig->get('smtp_on')->willReturn(TRUE);
    $this->mockConfig->get('smtp_host')->willReturn('');
    $this->mockConfig->get('smtp_hostbackup')->willReturn('');
    $this->mockConfig->get('smtp_port')->willReturn('');
    $this->mockConfig->get('smtp_protocol')->willReturn('');
    $this->mockConfig->get('smtp_autotls')->willReturn(TRUE);
    $this->mockConfig->get('smtp_timeout')->willReturn('');
    $this->mockConfig->get('smtp_username')->willReturn('');
    $this->mockConfig->get('smtp_password')->willReturn('');
    $this->mockConfig->get('smtp_from')->willReturn('');
    $this->mockConfig->get('smtp_fromname')->willReturn('');
    $this->mockConfig->get('smtp_allowhtml')->willReturn(FALSE);
    $this->mockConfig->get('smtp_client_hostname')->willReturn('');
    $this->mockConfig->get('smtp_client_helo')->willReturn('');
    $this->mockConfig->get('smtp_debugging')->willReturn('');
    $this->mockConfig->get('smtp_debug_level')->willReturn(1);
    $this->mockConfig->get('smtp_keepalive')->willReturn(FALSE);
    $this->mockConfig->get('smtp_reroute_address')->willReturn('');
  }

  /**
   * Test if enabled message is properly shown.
   */
  public function testBuildFormEnabledMessage() {
    $this->setDefaultConfig();
    $this->mockConfig->get('smtp_on')->willReturn(TRUE);

    $formBuilder = SMTPConfigForm::create($this->mockContainer->reveal());

    $form = [];
    $formBuilder->buildForm($form, new FormState());
    $this->mockMessenger->addMessage(Argument::which('getUntranslatedString', 'SMTP module is active.'))->shouldHaveBeenCalled();
  }

  /**
   * Test if enabled message is properly shown.
   */
  public function testBuildFormDisabledMessage() {
    $this->setDefaultConfig();
    $this->mockConfig->get('smtp_on')->willReturn(FALSE);

    $formBuilder = SMTPConfigForm::create($this->mockContainer->reveal());

    $form = [];
    $formBuilder->buildForm($form, new FormState());
    $this->mockMessenger->addMessage(Argument::which('getUntranslatedString', 'SMTP module is INACTIVE.'))->shouldHaveBeenCalled();
  }

  /**
   * Test form id.
   */
  public function testGetFormId() {
    $formBuilder = SMTPConfigForm::create($this->mockContainer->reveal());

    $form_id = $formBuilder->getFormId();
    $this->assertEquals('smtp_admin_settings', $form_id);
  }

}
