<?php

namespace Drupal\media_entity_kaltura\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity_kaltura\Plugin\media\Source\Kaltura;

/**
 * Plugin implementation of the 'kaltura_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "kaltura_embed",
 *   label = @Translation("Kaltura Embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class KalturaEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => '400',
      'height' => '285',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Width of embedded player. Suggested value: 400'),
    ];

    $elements['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Height of embedded player. Suggested value: 285'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [
      $this->t('Width: @width', [
        '@width' => $this->getSetting('width'),
      ]),
      $this->t('Height: @height', [
        '@height' => $this->getSetting('height'),
      ]),
    ];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\media\MediaInterface $media_entity */
    $media_entity = $items->getEntity();

    $element = [];
    if (($type = $media_entity->getSource()) && $type instanceof Kaltura) {
      foreach ($items as $delta => $item) {
        $entry_id = $item->value;

        if ($entry_id) {
          $element[$delta] = [
            '#type' => 'kaltura_player',
            '#kaltura_entry_id' => $entry_id,
            '#width' => $this->getSetting('width'),
            '#height' => $this->getSetting('height'),
          ];
        }
      }
    }

    return $element;
  }

}
