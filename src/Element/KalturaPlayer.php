<?php

namespace Drupal\media_entity_kaltura\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Url;

/**
 * Provides a render element to a Kaltura player.
 *
 * @RenderElement("kaltura_player")
 */
class KalturaPlayer extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderKalturaPlayerElement'],
      ],
      // Kaltura Entry ID the player should be initialized on.
      '#kaltura_entry_id' => NULL,
      // ID of the Kaltura partner.
      '#kaltura_partner_id' => NULL,
      // UI Conf ID from Kaltura.
      '#kaltura_uiconf_id' => NULL,
      // Width of the player in pixels.
      '#width' => 400,
      // Height of the player in pixels.
      '#height' => 285,
    ];
  }

  /**
   * View element pre render callback.
   */
  public static function preRenderKalturaPlayerElement($element) {
    $config = \Drupal::config('media_entity_kaltura.settings');

    $partner_id = $element['#kaltura_partner_id'] ?: $config->get('partner_id');
    $ui_conf_id = $element['#kaltura_uiconf_id'] ?: $config->get('ui_conf_id');

    $cacheability = CacheableMetadata::createFromRenderArray($element);
    $cacheability->addCacheableDependency($config);

    if (!isset($element['#attributes']['id'])) {
      $element['#attributes']['id'] = Html::getUniqueId($element['#kaltura_entry_id']);
    }

    $src = Url::fromUri("//cdnapi.kaltura.com/p/$partner_id/sp/{$partner_id}00/embedIframeJs/uiconf_id/$ui_conf_id/partner_id/$partner_id", [
      'absolute' => TRUE,
    ]);
    $element['player'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => $element['#attributes']['id'],
        'class' => ['media-entity-kaltura-player'],
        'data-kaltura-entry-id' => $element['#kaltura_entry_id'],
        'data-kaltura-partner-id' => $partner_id,
        'data-kaltura-ui-conf-id' => $ui_conf_id,
        'style' => 'width: ' . $element['#width'] . 'px; height: ' . $element['#height'] . 'px;',
      ],
      '#attached' => [
        'html_head' => [
          [
            [
              '#type' => 'html_tag',
              '#tag' => 'script',
              '#attributes' => [
                'type' => 'text/javascript',
                'src' => $src->toString(),
              ],
            ],
            'media-entity-kaltura-js',
          ],
        ],
        'library' => [
          'media_entity_kaltura/player',
        ],
      ],
    ];

    $cacheability->applyTo($element);

    return $element;
  }

}
