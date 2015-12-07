<?php

/**
 * @file
 * Contains Drupal\subpathauto\PathProcessor.
 */

namespace Drupal\subpathauto;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 */
class PathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * The path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The config factory
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Builds PathProcessor object.
   *
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The path processor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(InboundPathProcessorInterface $path_processor, ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    $this->pathProcessor = $path_processor;
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
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
    $path = ltrim($path, '/');
    while ($path_array = explode('/', $path)) {
      $subpath[] = array_pop($path_array);
      if (empty($path_array)) {
        break;
      }
      $path = implode('/', $path_array);
      $processed_path = $this->pathProcessor->processInbound('/' . $path, $request);
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
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleableMetadata = NULL) {
    if (isset($options['absolute']) && $options['absolute']) {
      return $path;
    }
    $original_path = $path;
    $subpath = array();
    $path = ltrim($path, '/');
    while ($path_array = explode('/', $path)) {
      $subpath[] = array_pop($path_array);
      if (empty($path_array)) {
        break;
      }
      $path = implode('/', $path_array);
      $processed_path = $this->pathProcessor->processOutbound('/' . $path, $options, $request);
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
   *   Path that might contain language prefix.
   * @return string $path info
   *   Path without language prefix.
   */
  protected function getPath($path_info) {
    // @todo Try to find a better solution. See also RedirectSubscriber checkRedirect().
    $config = $this->configFactory->get('language.negotiation')->get('url');
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $language_prefix = $config['prefixes'][$langcode];
    $path_info = trim($path_info, '/');
    if ($language_prefix) {
      $parts = explode('/', $path_info);
      array_shift($parts);
      $path_info = implode('/', $parts);
    }
    return '/' . $path_info;
  }

}
