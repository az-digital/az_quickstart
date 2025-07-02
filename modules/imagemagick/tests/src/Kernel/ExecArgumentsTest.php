<?php

declare(strict_types=1);

namespace Drupal\Tests\imagemagick\Kernel;

use Drupal\imagemagick\ArgumentMode;
use Drupal\imagemagick\Event\ImagemagickExecutionEvent;
use Drupal\imagemagick\ImagemagickExecArguments;
use Drupal\imagemagick\ImagemagickExecManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests for ImagemagickExecArguments.
 *
 * @group imagemagick
 */
class ExecArgumentsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['imagemagick', 'file_mdm', 'sophron'];

  /**
   * Test arguments handling.
   */
  public function testArguments(): void {
    $arguments = new ImagemagickExecArguments(\Drupal::service(ImagemagickExecManagerInterface::class));

    // Setup a list of arguments.
    $arguments
      ->add(["-resize", "100x75!"])
      // Internal argument.
      ->add(["INTERNAL"], ArgumentMode::Internal)
      ->add(["-quality", "75"])
      // Prepend argument.
      ->add(["-hoxi", "76"], ArgumentMode::PostSource, 0)
      // Pre source argument.
      ->add(["-density", "25"], ArgumentMode::PreSource)
      // Another internal argument.
      ->add(["GATEAU"], ArgumentMode::Internal)
      // Another pre source argument.
      ->add(["-auchocolat", "90"], ArgumentMode::PreSource)
      // Add two arguments with additional info.
      ->add(
        ["-addz", "150"],
        ArgumentMode::PostSource,
        ImagemagickExecArguments::APPEND,
        [
          'foo' => 'bar',
          'qux' => 'der',
        ]
      )
      ->add(
        ["-addz", "200"],
        ArgumentMode::PostSource,
        ImagemagickExecArguments::APPEND,
        [
          'wey' => 'lod',
          'foo' => 'bar',
        ]
      );

    // Test find arguments skipping identifiers.
    $this->assertSame([4], array_keys($arguments->find('/^INTERNAL/')));
    $this->assertSame([9], array_keys($arguments->find('/^GATEAU/')));
    $this->assertSame([10], array_keys($arguments->find('/^\-auchocolat/')));
    $this->assertSame([12, 14], array_keys($arguments->find('/^\-addz/')));
    $this->assertSame([12, 13, 14, 15], array_keys($arguments->find('/.*/', NULL, ['foo' => 'bar'])));
    $this->assertSame([], $arguments->find('/.*/', NULL, ['arw' => 'moo']));

    // Check resulting command line strings.
    $this->assertSame('[-density] [25] [-auchocolat] [90]', $arguments->toDebugString(ArgumentMode::PreSource));
    $this->assertSame("[-hoxi] [76] [-resize] [100x75!] [-quality] [75] [-addz] [150] [-addz] [200]", $arguments->toDebugString(ArgumentMode::PostSource));

    // Add arguments with a specific index.
    $arguments
      ->add(["-ix", "aa"], ArgumentMode::PostSource, 12)
      ->add(["-ix", "bb"], ArgumentMode::PostSource, 12);
    $this->assertSame([12, 14], array_keys($arguments->find('/^\-ix/')));
    $this->assertSame("[-hoxi] [76] [-resize] [100x75!] [-quality] [75] [-ix] [bb] [-ix] [aa] [-addz] [150] [-addz] [200]", $arguments->toDebugString(ArgumentMode::PostSource));
  }

  /**
   * Test prepend argument strings with quoted tokens.
   */
  public function testPrependQuotedArguments(): void {
    $eventDispatcher = \Drupal::service(EventDispatcherInterface::class);
    $arguments = new ImagemagickExecArguments(\Drupal::service(ImagemagickExecManagerInterface::class));

    $input = "This is a string that \"will be\" highlighted when your 'regular expression' matches something.";
    $expected = "[This] [is] [a] [string] [that] [will be] [highlighted] [when] [your] [regular expression] [matches] [something.]";

    \Drupal::configFactory()->getEditable('imagemagick.settings')
      ->set('prepend', $input)
      ->save();
    $eventDispatcher->dispatch(new ImagemagickExecutionEvent($arguments), ImagemagickExecutionEvent::PRE_IDENTIFY_EXECUTE);
    $this->assertSame($expected, $arguments->toDebugString(ArgumentMode::PreSource));

    $arguments->reset();

    $input = "This is \"also \\\"valid\\\"\" and 'more \\'valid\\'' as a string.";
    $expected = "[This] [is] [also \"valid\"] [and] [more 'valid'] [as] [a] [string.]";

    \Drupal::configFactory()->getEditable('imagemagick.settings')
      ->set('prepend', $input)
      ->save();
    $eventDispatcher->dispatch(new ImagemagickExecutionEvent($arguments), ImagemagickExecutionEvent::PRE_IDENTIFY_EXECUTE);
    $this->assertSame($expected, $arguments->toDebugString(ArgumentMode::PreSource));
  }

}
