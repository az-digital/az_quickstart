<?php

namespace Drupal\Tests\ctools\Kernel;

use Drupal\Core\Plugin\Context\ContextInterface;

/**
 * @coversDefaultClass \Drupal\ctools\Plugin\Relationship\TypedDataRelationship
 * @group CTools
 */
class TypedDataRelationshipPluginTest extends RelationshipsTestBase {

  /**
   * @covers ::getName
   */
  public function testRelationshipName() {
    /** @var \Drupal\ctools\Plugin\RelationshipInterface $nid_plugin */
    $nid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:nid');
    $this->assertSame('nid', $nid_plugin->getName());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $uuid_plugin */
    $uuid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:uuid');
    $this->assertSame('uuid', $uuid_plugin->getName());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $title_plugin */
    $title_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:title');
    $this->assertSame('title', $title_plugin->getName());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $body_plugin */
    $body_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:body');
    $this->assertSame('body', $body_plugin->getName());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $uid_plugin */
    $uid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:uid');
    $this->assertSame('uid', $uid_plugin->getName());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $mail_plugin */
    $mail_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:user:mail');
    $this->assertSame('mail', $mail_plugin->getName());
  }

  /**
   * @covers ::getRelationship
   */
  public function testRelationship() {
    /** @var \Drupal\ctools\Plugin\RelationshipInterface $nid_plugin */
    $nid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:nid');
    $nid_plugin->setContextValue('base', $this->entities['node1']);
    $relationship = $nid_plugin->getRelationship();
    $this->assertTrue($relationship instanceof ContextInterface);
    $this->assertEquals('integer', $relationship->getContextDefinition()->getDataType());
    $this->assertTrue($relationship->hasContextValue());
    $this->assertEquals($this->entities['node1']->id(), $relationship->getContextValue());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $uuid_plugin */
    $uuid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:uuid');
    $uuid_plugin->setContextValue('base', $this->entities['node1']);
    $relationship = $uuid_plugin->getRelationship();
    $this->assertTrue($relationship instanceof ContextInterface);
    $this->assertEquals('string', $relationship->getContextDefinition()->getDataType());
    $this->assertTrue($relationship->hasContextValue());
    $this->assertEquals($this->entities['node1']->uuid(), $relationship->getContextValue());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $title_plugin */
    $title_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:title');
    $title_plugin->setContextValue('base', $this->entities['node1']);
    $relationship = $title_plugin->getRelationship();
    $this->assertTrue($relationship instanceof ContextInterface);
    $this->assertEquals('string', $relationship->getContextDefinition()->getDataType());
    $this->assertTrue($relationship->hasContextValue());
    $this->assertEquals($this->entities['node1']->label(), $relationship->getContextValue());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $body_plugin */
    $body_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:body');
    $body_plugin->setContextValue('base', $this->entities['node1']);
    $relationship = $body_plugin->getRelationship();
    $this->assertTrue($relationship instanceof ContextInterface);
    $this->assertEquals('string', $relationship->getContextDefinition()->getDataType());
    $this->assertTrue($relationship->hasContextValue());
    $this->assertEquals($this->entities['node1']->get('body')->first()->get('value')->getValue(), $relationship->getContextValue());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $uid_plugin */
    $uid_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:node:uid');
    $uid_plugin->setContextValue('base', $this->entities['node3']);
    $relationship = $uid_plugin->getRelationship();
    $this->assertTrue($relationship instanceof ContextInterface);
    $this->assertEquals('integer', $relationship->getContextDefinition()->getDataType());
    $this->assertTrue($relationship->hasContextValue());
    $this->assertEquals($this->entities['node3']->getOwnerId(), $relationship->getContextValue());

    /** @var \Drupal\ctools\Plugin\RelationshipInterface $mail_plugin */
    $mail_plugin = $this->relationshipManager->createInstance('typed_data_relationship:entity:user:mail');
    $mail_plugin->setContextValue('base', $this->entities['user']);
    $relationship = $mail_plugin->getRelationship();
    $this->assertTrue($relationship instanceof ContextInterface);
    $this->assertEquals('email', $relationship->getContextDefinition()->getDataType());
    $this->assertTrue($relationship->hasContextValue());
    $this->assertEquals($this->entities['user']->getEmail(), $relationship->getContextValue());
  }

}
