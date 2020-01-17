<?php

namespace Drupal\axelerant\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get page content.
 *
 * @RestResource(
 *   id = "rest_get_page",
 *   label = @Translation("Rest get page"),
 *   uri_paths = {
 *     "canonical" = "/page_json/{siteapikey}/{nodeid}"
 *   }
 * )
 */
class RestGetPage extends ResourceBase {

  /**
   * Entity type manager to get entity or node.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Configuration factory object to get site information configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs RestGetPage rest resource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param string $siteapikey
   *   Configured site api key in system settings.
   * @param string $nodeid
   *   The node id.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws exception access denied.
   */
  public function get($siteapikey, $nodeid) {
    // Get site information configuration.
    $config = $this->configFactory->get('system.site');
    // Get site Api Key value.
    $configuredApiKey = $config->get('site_api_key');
    // Get node object from node id.
    $node = $this->entityTypeManager->getStorage('node')->load($nodeid);
    // Access denied if site Api Key is different or node type is not page.
    if ($node->bundle() !== 'page' || $configuredApiKey !== $siteapikey) {
      throw new AccessDeniedHttpException('Access denied');
    }
    return new ResourceResponse($node, 200);
  }

}
