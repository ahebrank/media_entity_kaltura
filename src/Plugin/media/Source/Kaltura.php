<?php

namespace Drupal\media_entity_kaltura\Plugin\media\Source;

use GuzzleHttp\Client;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media_entity_kaltura\KalturaSdkInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeExtensionGuesser;

/**
 * Expose Kaltura as source of Drupal media.
 *
 * @MediaSource(
 *   id = "kaltura",
 *   label = @Translation("Kaltura"),
 *   description = @Translation("Use Kaltura entries for reusable media."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "generic.png"
 * )
 */
class Kaltura extends MediaSourceBase {

  /**
   * The http client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The logger channel for media.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Kaltura SDK service.
   *
   * @var \Drupal\media_entity_kaltura\KalturaSdkInterface
   */
  protected $kalturaSdk;

  /**
   * Guesser of file extension based on mime type.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeExtensionGuesser
   */
  protected $mimeTypeExtensionGuesser;

  /**
   * Kaltura constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \GuzzleHttp\Client $http_client
   *   The http client service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\media_entity_kaltura\KalturaSdkInterface $kaltura_sdk
   *   The Kaltura SDK service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, Client $http_client, LoggerInterface $logger, KalturaSdkInterface $kaltura_sdk) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);

    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->kalturaSdk = $kaltura_sdk;
    $this->mimeTypeExtensionGuesser = new MimeTypeExtensionGuesser();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('logger.channel.media_entity_kaltura'),
      $container->get('media_entity_kaltura.sdk')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'thumbnails_directory' => 'public://kaltura_thumbnails',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $configuration = $this->getConfiguration();

    $form['thumbnails_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Thumbnails location'),
      '#default_value' => $configuration['thumbnails_directory'],
      '#description' => $this->t('Thumbnails will be fetched from Kaltura for local usage. This is the URI of the directory where they will be placed.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $thumbnails_directory = $form_state->getValue('thumbnails_directory');
    if (!file_valid_uri($thumbnails_directory)) {
      $form_state->setErrorByName('thumbnails_directory', $this->t('@path is not a valid path.', [
        '@path' => $thumbnails_directory,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    switch ($attribute_name) {
      case 'thumbnail_uri':
        $thumbnail_uri = $this->getLocalThumbnailUri($media);
        if ($thumbnail_uri) {
          return $thumbnail_uri;
        }
        break;
    }

    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'thumbnail_uri' => $this->t('Local URI of the thumbnail'),
    ];
  }

  /**
   * Returns the local URI for a resource thumbnail.
   *
   * If the thumbnail is not already locally stored, this method will attempt
   * to download it.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media whose thumbnail is requested.
   *
   * @return string|null
   *   The local thumbnail URI, or NULL if it could not be downloaded, or if the
   *   resource has no thumbnail at all.
   */
  protected function getLocalThumbnailUri(MediaInterface $media) {
    $kaltura_client = $this->kalturaSdk->getAdminClient();

    if (!$kaltura_client) {
      $this->logger->error($this->t('Kaltura client not initialized. Check API configuration.'));
      return NULL;
    }

    $thumb_service = $kaltura_client->getThumbAssetService();
    if (!$thumb_service) {
      $this->logger->error($this->t('Unable to initialize Kaltura thumbnail retrieval service.'));
      return NULL;
    }

    $thumbnail = $thumb_service->getByEntryId($this->getSourceFieldValue($media));
    /** @var \Kaltura\Client\Type\ThumbAsset $thumbnail */
    $thumbnail = reset($thumbnail);
    $thumbnail_url = $thumb_service->getUrl($thumbnail->id);

    if (!$thumbnail_url) {
      $this->logger->error($this->t('Kaltura client could not find a thumbnail.'));
      return NULL;
    }

    $response = $this->httpClient->get($thumbnail_url);
    if ($response->getStatusCode() == 200) {
      $extension = 'jpg';
      if ($response->hasHeader('Content-Type')) {
        $extension = $this->mimeTypeExtensionGuesser->guess($response->getHeader('Content-Type')[0]);
      }

      $directory = $this->getConfiguration()['thumbnails_directory'];

      if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        $this->logger->error($this->t('Could not prepare thumbnail destination directory {dir} for Kaltura media.', [
          '{dir}' => $directory,
        ]));
        return NULL;
      }

      $local_uri = $directory . '/' . Crypt::hashBase64($thumbnail->id) . '.' . $extension;

      $success = file_unmanaged_save_data($response->getBody()->getContents(), $local_uri, FILE_EXISTS_REPLACE);
      if ($success) {
        return $local_uri;
      }

      $this->logger->error($this->t('Could not save the Kaltura thumbnail to {local_file}', [
        'local_file' => $local_uri,
      ]));
    }

    $this->logger->error($this->t('Unable to retrieve Kaltura thumbnail.'));
    return NULL;
  }

}
