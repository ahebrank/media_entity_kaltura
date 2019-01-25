<?php

namespace Drupal\media_entity_kaltura\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Administration settings of the module.
 */
class KalturaAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config($this->getEditableConfigNames()[0]);

    $form['partner_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Kaltura partner ID'),
      '#required' => TRUE,
      '#description' => $this->t('Kaltura Partner ID as provided to you by Kaltura.'),
      '#default_value' => $config->get('partner_id'),
    ];

    $form['ui_conf_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Kaltura UI Conf ID'),
      '#required' => TRUE,
      '#description' => $this->t('Kaltura UI Conf as provided to you by Kaltura.'),
      '#default_value' => $config->get('ui_conf_id'),
    ];

    $form['server_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Kaltura server URL'),
      '#required' => TRUE,
      '#description' => $this->t('URL of Kaltura server where your multimedia is hosted.'),
      '#default_value' => $config->get('server_url'),
    ];

    $form['admin_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Admin secret'),
      '#required' => TRUE,
      '#description' => $this->t('Kaltura admin secret.'),
      '#default_value' => $config->get('admin_secret'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable($this->getEditableConfigNames()[0])
      ->set('partner_id', $form_state->getValue('partner_id'))
      ->set('ui_conf_id', $form_state->getValue('ui_conf_id'))
      ->set('server_url', $form_state->getValue('server_url'))
      ->set('admin_secret', $form_state->getValue('admin_secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['media_entity_kaltura.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_entity_kaltura_admin_form';
  }

}
