<?php

namespace Drupal\Tests\rat\v1;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\rat\v1\RenderArray;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertSame;

class RenderArrayTest extends TestCase {

  public function testCreate() {
    $this->assertSame([], RenderArray::create()->toRenderable());
  }

  public function testBuildOn() {
    $array = [123];
    $build = RenderArray::alter($array);
    $result = $build->toRenderable();
    $this->assertSame($array, $result);
  }

  public function testBuildOnReference() {
    $array = [123];
    $build = RenderArray::alter($array);
    $build->get(7)->setValue(456, NULL);
    $result = $build->toRenderable();
    $this->assertSame($array + [7 => 456], $result);
  }

  public function testGet() {
    $array = [['q' => 123]];
    $build = RenderArray::alter($array);

    $result = $build->get(0, 'q')->toRenderable();
    $this->assertSame(123, $result);
  }

  public function testGetDotted() {
    $array = [['q' => 123]];
    $build = RenderArray::alter($array);

    $result = $build->getDotted('0.q')->toRenderable();
    $this->assertSame(123, $result);
  }

  public function testGetValue() {
    $array = [123];
    $build = RenderArray::alter($array);

    $result = $build->get('0')->getValue();
    $this->assertSame(123, $result);
  }

  public function testGetValueReference() {
    $array = [123];
    $build = RenderArray::alter($array);

    $result =& $build->get('0')->getValue();
    $result = 456;
    $this->assertSame([456], $array);
  }

  public function testSet() {
    $array = [123];
    $build = RenderArray::alter($array);

    $build->get(7)->setValue(456, NULL);

    $result = $build->toRenderable();
    $this->assertSame($array + [7 => 456], $result);
  }

  public function testSetCacheability() {
    $array = [123];
    $build = RenderArray::alter($array);

    $cacheability = (new CacheableMetadata())
      ->addCacheTags(['t1', 't2'])
      ->addCacheContexts(['c1', 'c2'])
      ->setCacheMaxAge(123)
    ;

    $build->get(7)->setValue([456], $cacheability);

    $result = $build->toRenderable();
    $this->assertSame($array + [7 => [456, '#cache' => ['tags' => ['t1', 't2'], 'contexts' => ['c1', 'c2'], 'max-age' => 123]]], $result);
  }

  public function testIsset() {
    $array = [123];
    $build = RenderArray::alter($array);

    $this->assertSame(TRUE, $build->isset());
    $this->assertSame(TRUE, $build->get(0)->isset());
    $this->assertSame(FALSE, $build->get(1)->isset());
  }

  public function testIsEmptyBuild() {
    $array = [123, [], ['#cache' => [999]]];
    $build = RenderArray::alter($array);

    $this->assertSame(FALSE, $build->isEmptyBuild());
    $this->assertSame(FALSE, $build->get(0)->isEmptyBuild());
    $this->assertSame(TRUE, $build->get(1)->isEmptyBuild());
    $this->assertSame(TRUE, $build->get(2)->isEmptyBuild());
    $this->assertSame(TRUE, $build->get('not-existing')->isEmptyBuild());
  }

  public function testChildren() {
    $array = ['p' => [123], 'q' => [456]];
    $build = RenderArray::alter($array);

    $this->assertEquals(['p' => $build->get('p'), 'q' => $build->get('q')], $build->children());
  }

  public function testAddAnother() {
    $array = [[], 123];
    $build = RenderArray::alter($array);
    $build->addAnother();
    $build->addAnother();

    $this->assertSame([[], 123, NULL, NULL], $build->toRenderable());

    $build->get('new')->addAnother();
    $build->get('new')->addAnother();
    $this->assertSame([NULL, NULL], $build->get('new')->toRenderable());

    $this->expectException(\RuntimeException::class);
    $build->get(1)->addAnother();
  }

  public function testAttachLibrary() {
    $array = [123];
    $build = RenderArray::alter($array);
    $build->attachLibrary('foo')->attachLibrary('bar');

    $this->assertSame(['foo', 'bar'], $build->get('#attached', 'library')->getValue());
  }

  public function testAttachDrupalSettings() {
    $array = [123];
    $build = RenderArray::alter($array);
    $build->attachDrupalSettings('foo', ['foo-setting']);

    $this->assertSame(['foo' => ['foo-setting']], $build->get('#attached', 'drupalSettings')->getValue());
  }

  public function testAttachHeader() {
    $array = [123];
    $build = RenderArray::alter($array);
    $build->attachHeader('foo', 'bar', TRUE);

    $this->assertSame([['foo', 'bar', TRUE]], $build->get('#attached', 'http_header')->getValue());
  }

  public function testAttachFeed() {
    $array = [123];
    $build = RenderArray::alter($array);
    $build->attachFeed('foo', 'bar');

    $this->assertSame([['foo', 'bar']], $build->get('#attached', 'feed')->getValue());
  }

  public function testAttachHeadLink() {
    $array = [123];
    $build = RenderArray::alter($array);
    $build->attachHeadLink('href', 'rel', 'title', 'type', 'hreflang', TRUE);

    $this->assertSame([[['href' => 'href', 'rel' => 'rel', 'title' => 'title', 'type' => 'type', 'hreflang' => 'hreflang'], TRUE]], $build->get('#attached', 'html_head_link')->getValue());
  }

  public function testAttachHead() {
    $array = [123];
    $build = RenderArray::alter($array);
    $build->attachHead('foo', [1, 2]);

    $this->assertSame([[[1, 2], 'foo']], $build->get('#attached', 'html_head')->getValue());
  }

  public function testAddCacheability() {
    $array = ['foo' => [123]];
    $build = RenderArray::alter($array);

    $cacheability = (new CacheableMetadata())
      ->addCacheTags(['t1', 't2'])
      ->addCacheContexts(['c1', 'c2'])
      ->setCacheMaxAge(123)
    ;
    $build->addCacheability($cacheability);

    $result = $build->toRenderable();
    // @fixme
    $this->assertSame($array + ['#cache' => ['tags' => ['t1', 't2'], 'contexts' => ['c1', 'c2'], 'max-age' => 123]], $result);
  }

  public function testSetUncacheable() {
    $array = [123];
    $build = RenderArray::alter($array);

    $build->setUncacheable();

    $result = $build->toRenderable();
    $this->assertSame($array + ['#cache' => ['max-age' => 0]], $result);
  }

  /**
   * @dataProvider provideGetAccessResultData
   */
  public function testGetAccessResult($value, $expected) {
    $array = [123];
    if (isset($value)) {
      $array['#access'] = $value;
    }
    $build = RenderArray::alter($array);

    $actual = $build->getAccessResult()->isAllowed();
    $this->assertSame($expected, $actual);
  }

  public function provideGetAccessResultData(): array {
    return [
      [NULL, TRUE],
      [TRUE, TRUE],
      [0, TRUE],
      ['', TRUE],
      [new \stdClass(), TRUE],
      [FALSE, FALSE],
      [AccessResult::allowed(), TRUE],
      [AccessResult::neutral(), FALSE],
      [AccessResult::forbidden(), FALSE],
    ];
  }

  public function testRestrictAccessResult() {
    $array = [123];
    $build = RenderArray::alter($array);

    $build->restrictAccessResult(AccessResult::allowed());
    $accessValue = $build->get('#access')->getValue();
    // AccessResult without cacheability is optimized to boolean.
    assertSame($accessValue, TRUE);

    $accessResultForbiddenWithCacheability = AccessResult::forbidden()->addCacheTags(['foo']);
    $build->restrictAccessResult($accessResultForbiddenWithCacheability);
    $accessValue = $build->get('#access')->getValue();
    assertEquals($accessValue, $accessResultForbiddenWithCacheability);

    $build->restrictAccessResult(AccessResult::forbidden());
    $accessValue = $build->get('#access')->getValue();
    // This carries cacheability due to the anatomy of AccessResult::andIf()
    assertEquals($accessValue, AccessResult::forbidden()
      ->addCacheTags(['foo'])
    );
  }

  public function testRestrictAccess() {
    $array = [123];
    $build = RenderArray::alter($array);

    $build->restrictAccess(TRUE, NULL);
    $accessValue = $build->get('#access')->getValue();
    assertSame($accessValue, TRUE);

    $cacheability1 = (new CacheableMetadata())->addCacheTags(['foo']);
    $build->restrictAccess(TRUE, $cacheability1);
    $accessValue = $build->get('#access')->getValue();
    assertEquals($accessValue, AccessResult::allowed()->addCacheableDependency($cacheability1));

    $cacheability2 = (new CacheableMetadata())->addCacheTags(['bar']);
    $build->restrictAccess(FALSE, $cacheability2);
    $accessValue = $build->get('#access')->getValue();
    assertEquals($accessValue, AccessResult::neutral()
      ->addCacheableDependency($cacheability1)
      ->addCacheableDependency($cacheability2)
    );

    $build->restrictAccess(FALSE, NULL);
    $accessValue = $build->get('#access')->getValue();
    assertEquals($accessValue, AccessResult::neutral('')
      ->addCacheableDependency($cacheability1)
      ->addCacheableDependency($cacheability2)
    );
  }

  public function testSetNoAccess() {
    $array = [123];
    $build = RenderArray::alter($array);

    $build->restrictAccess(TRUE, (new CacheableMetadata())->addCacheTags(['foo']));
    $build->setNoAccess((new CacheableMetadata())->addCacheTags(['bar']));
    $accessValue = $build->get('#access')->getValue();
    assertEquals($accessValue, AccessResult::neutral()->addCacheTags(['foo', 'bar']));
  }

}
