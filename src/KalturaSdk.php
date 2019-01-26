<?php

namespace Drupal\media_entity_kaltura;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Kaltura\Client\ApiException;
use Kaltura\Client\Client;
use Kaltura\Client\ClientException;
use Kaltura\Client\Configuration;
use Kaltura\Client\Enum\SessionType;
use Kaltura\Client\Type\User;
use Psr\Log\LoggerInterface;

/**
 * Drupal representation of Kaltura PHP SDK.
 */
class KalturaSdk implements KalturaSdkInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * KalturaSdk constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user, LoggerInterface $logger) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminClient() {
    $config = $this->configFactory->get('media_entity_kaltura.settings');

    try {
      $kaltura_config = new Configuration();
      $kaltura_config->setServiceUrl($config->get('server_url'));
      $kaltura_client = new Client($kaltura_config);
      $kaltura_client->setPartnerId($config->get('partner_id'));

      $kaltura_user = new User();

      // When the session type is SessionType::ADMIN, the 'privileges' parameter
      // is ignored. See
      // https://knowledge.kaltura.com/kalturas-api-authentication-and-security
      $session = $kaltura_client->getSessionService()->start($config->get('admin_secret'), $kaltura_user->id, SessionType::ADMIN, $kaltura_client->getPartnerId(), KalturaSdkInterface::EXPIRY, '');

      $kaltura_client->setKs($session);

      return $kaltura_client;
    }
    catch (ApiException $e) {
      $this->logger->error($e->getMessage());
    }
    catch (ClientException $e) {
      $this->logger->error($e->getMessage());
    }

    return NULL;
  }

}
