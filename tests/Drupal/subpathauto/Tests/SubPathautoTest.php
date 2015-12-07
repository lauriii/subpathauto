<?php

/**
* @file
* Contains \Drupal\subpathauto\Tests\SubPathautoTest.
*/

namespace Drupal\subpathauto\Tests;

use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\subpathauto\PathProcessor;

/**
 * Unit tests for Sub-Pathauto functionality.
 */
class SubPathautoTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\PathProcessor\PathProcessorAlias|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $aliasProcessor;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The service under testing.
   *
   * @var \Drupal\subpathauto\PathProcessor
   */
  protected $sut;

  public function setUp() {
    parent::setUp();

    $this->aliasProcessor = $this->getMockBuilder('Drupal\Core\PathProcessor\PathProcessorAlias')
      ->disableOriginalConstructor()
      ->getMock();

    $this->languageNegotiation = $this->getMockBuilder('Drupal\language\Annotation\LanguageNegotiation')
      ->disableOriginalConstructor()
      ->getMock();

    $this->configFactory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $this->configFactory->expects($this->any())
      ->method('get')
      ->with('language.negotiation')
      ->willReturn($this->languageNegotiation);

    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn(new Language());

    $this->sut = new PathProcessor($this->aliasProcessor, $this->configFactory, $this->languageManager);
  }

  /**
   * Tests the basic functionality of the processInbound() method.
   */
  public function testInboundSubPath() {
    $this->aliasProcessor->expects($this->any())
      ->method('processInbound')
      ->will($this->returnCallback([$this, 'pathAliasCallback']));

    // Look up a subpath of the 'content/first-node' alias.
    $processed = $this->sut->processInbound('/content/first-node/a', Request::create('content/first-node/a'));
    $this->assertEquals('/node/1/a', $processed);

    // Look up a subpath of the 'content/first-node-test' alias.
    $processed = $this->sut->processInbound('/content/first-node-test/a', Request::create('content/first-node-test/a'));
    $this->assertEquals('/node/1/test/a', $processed);

    // Look up an admin sub-path of the 'content/first-node' alias without
    // disabling sub-paths for admin.
    $processed = $this->sut->processInbound('/content/first-node/edit', Request::create('content/first-node/edit'));
    $this->assertEquals('/node/1/edit', $processed);

    // Look up an admin sub-path without disabling sub-paths for admin.
    $processed = $this->sut->processInbound('/malicious-path/modules', Request::create('malicious-path/modules'));
    $this->assertEquals('/admin/modules', $processed);

    // @todo Add tests for the functionality to disallow admin subpaths once it
    // has been implemented.
  }


  /**
   * Tests the basic functionality of the processInbound() method.
   */
  public function testInboundAlreadyProcessed() {
    // The subpath processor should ignore this and not pass it on to the
    // alias processor.
    $processed = $this->sut->processInbound('node/1', Request::create('content/first-node'));
  }

  /**
   * Return value callback for the getSystemPath() method on the mock alias manager.
   *
   * Ensures that by default the call to getPathAlias() will return the first argument
   * that was passed in. We special-case the paths for which we wish it to return an
   * actual alias.
   *
   * @return string
   */
  public function pathAliasCallback() {
    $args = func_get_args();
    switch($args[0]) {
      case '/content/first-node':
        return '/node/1';
      case '/content/first-node-test':
        return '/node/1/test';
      case '/malicious-path':
        return '/admin';
      case '':
        return '<front>';
      default:
        return $args[0];
    }
  }

}
