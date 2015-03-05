<?php

/**
 * @file
 * Contains Drupal\subpathauto\PathProcessor.
 */

namespace Drupal\subpathauto;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 */
class PathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  protected $pathProcessor;

  public function __construct(InboundPathProcessorInterface $path_processor) {
    $this->pathProcessor = $path_processor;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $request_path = $this->getPath($request->getPathInfo());

    if ($request_path !== $path) {
      return $path;
    }
    $original_path = $path;
    $subpath = array();
    while ($path_array = explode('/', $path)) {
      $subpath[] = array_pop($path_array);
      if (empty($path_array)) {
        break;
      }
      $path = implode('/', $path_array);
      $processed_path = $this->pathProcessor->processInbound($path, $request);
      if ($processed_path !== $path) {
        $path = $processed_path . '/' . implode('/', array_reverse($subpath));
        return $path;
      }
    }

    return $original_path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL) {
    if ($options['absolute']) {
      return $path;
    }
    $original_path = $path;
    $subpath = array();
    while ($path_array = explode('/', $path)) {
      $subpath[] = array_pop($path_array);
      if (empty($path_array)) {
        break;
      }
      $path = implode('/', $path_array);
      $processed_path = $this->pathProcessor->processOutbound($path, $options, $request);
      if ($processed_path !== $path) {
        $path = $processed_path . '/' . implode('/', array_reverse($subpath));
        return $path;
      }
    }

    return $original_path;
  }

  /**
   * Helper function to handle multilingual paths.
   *
   * @param string $path_info
   *   path that might contain language prefix.
   */
  protected function getPath($path_info) {
    $config = \Drupal::config('language.negotiation')->get('url');
    $parts = explode('/', ltrim($path_info, '/'));
    $prefix = array_shift($parts);
    $request_path = '';
    
    // Search prefix within added languages.
    foreach (\Drupal::languageManager()->getLanguages() as $language) {
      if (isset($config['prefixes'][$language->getId()]) && $config['prefixes'][$language->getId()] == $prefix) {
        // Rebuild $path with the language removed.
        return implode('/', $parts);
      }
    }
  }

}
