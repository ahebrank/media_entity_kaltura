<?php

namespace Drupal\media_entity_kaltura;

/**
 * Interface that represents Kaltura SDK within Drupal.
 */
interface KalturaSdkInterface {

  /**
   * Default SDK session timeout.
   *
   * @var int
   */
  const EXPIRY = 86400;

  /**
   * Fully initialized Kaltura SDK object with admin level of permissions.
   *
   * @param string $privileges
   *   Scope of privileges to include in the returned SDK object.
   *
   * @return \Kaltura\Client\Client
   *   Kaltura SDK object with admin level of permissions.
   */
  public function getAdminClient($privileges);

}
