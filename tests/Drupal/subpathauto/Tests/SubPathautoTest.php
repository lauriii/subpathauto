<?php

/**
* @file
* Contains \Drupal\subpathauto\Tests\SubPathautoTest.
*/

namespace Drupal\subpathauto\Tests;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\Core\PathProcessor\PathProcessorManager;
use Symfony\Component\HttpFoundation\Request;

use Drupal\subpathauto\PathProcessor;

/**
* Unit tests for Sub-Pathauto functaionality/
*/
class SubPathautoTest extends UnitTestCase {

  protected $processorManager;

  public static function getInfo() {
    return array(
      'name' => 'SubPathauto',
      'description' => 'Confirm that the UrlGenerator is functioning properly.',
      'group' => 'Routing',
    );
  }

  /**
   * Tests the basic functionality of the processInbound() method.
   */
  public function testInboundSubPath() {
    $alias_processor = $this->getMockBuilder('Drupal\Core\PathProcessor\PathProcessorAlias')
      ->disableOriginalConstructor()
      ->getMock();
    $alias_processor->expects($this->any())
      ->method('processInbound')
      ->will($this->returnCallback(array($this, 'pathAliasCallback')));
    $subpath_processor = new PathProcessor($alias_processor);

    // Look up a subpath of the 'content/first-node' alias.
    $processed = $subpath_processor->processInbound('content/first-node/a', Request::create('content/first-node/a'));
    $this->assertEquals('node/1/a', $processed);

    // Look up a subpath of the 'content/first-node-test' alias.
    $processed = $subpath_processor->processInbound('content/first-node-test/a', Request::create('content/first-node-test/a'));
    $this->assertEquals('node/1/test/a', $processed);

    // Look up an admin sub-path of the 'content/first-node' alias without
    // disabling sub-paths for admin.
    $processed = $subpath_processor->processInbound('content/first-node/edit', Request::create('content/first-node/edit'));
    $this->assertEquals('node/1/edit', $processed);

    // Look up an admin sub-path without disabling sub-paths for admin.
    $processed = $subpath_processor->processInbound('malicious-path/modules', Request::create('malicious-path/modules'));
    $this->assertEquals('admin/modules', $processed);

    // @todo Add tests for the functionality to disallow admin subpaths once it
    // has been implemented.
  }


  /**
   * Tests the basic functionality of the processInbound() method.
   */
  public function testInboundAlreadyProcessed() {
    $alias_processor = $this->getMockBuilder('Drupal\Core\PathProcessor\PathProcessorAlias')
      ->disableOriginalConstructor()
      ->getMock();
    $alias_processor->expects($this->never())
      ->method('processInbound');
    $subpath_processor = new PathProcessor($alias_processor);

    // The subpath processor should ignore this and not pass it on to the
    // alias processor.
    $processed = $subpath_processor->processInbound('node/1', Request::create('content/first-node'));
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
      case 'content/first-node':
        return 'node/1';
      case 'content/first-node-test':
        return 'node/1/test';
      case 'malicious-path':
        return 'admin';
      case '':
        return '<front>';
      default:
        return $args[0];
    }
  }

}