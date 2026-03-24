<?php

namespace Drupal\appointment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Appointment module settings.
 */
class AppointmentSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['appointment.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'appointment_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('appointment.settings');

    // Email notifications setting
    $form['email_section'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Email Notifications'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['email_section']['email_notifications_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable email notifications'),
      '#description' => $this->t('Send emails on appointment creation, modification, and cancellation.'),
      '#default_value' => $config->get('email_notifications_enabled'),
    ];

    $form['email_section']['notification_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Admin notification email'),
      '#description' => $this->t('Email address to receive appointment notifications.'),
      '#default_value' => $config->get('notification_email'),
      '#required' => FALSE,
    ];

    // Appointment settings
    $form['appointment_section'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Appointment Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['appointment_section']['default_appointment_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Default appointment duration (minutes)'),
      '#description' => $this->t('Default duration for new appointments.'),
      '#default_value' => $config->get('default_appointment_duration') ?? 30,
      '#min' => 15,
      '#max' => 480,
      '#step' => 15,
    ];

    $form['appointment_section']['max_advance_booking_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum advance booking (days)'),
      '#description' => $this->t('How far in advance users can book appointments.'),
      '#default_value' => $config->get('max_advance_booking_days') ?? 90,
      '#min' => 1,
      '#max' => 365,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $duration = $form_state->getValue('default_appointment_duration');
    if ($duration < 15 || $duration > 480) {
      $form_state->setErrorByName('default_appointment_duration',
        $this->t('Appointment duration must be between 15 and 480 minutes.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('appointment.settings');

    $config
      ->set('email_notifications_enabled', $form_state->getValue('email_notifications_enabled'))
      ->set('notification_email', $form_state->getValue('notification_email'))
      ->set('default_appointment_duration', $form_state->getValue('default_appointment_duration'))
      ->set('max_advance_booking_days', $form_state->getValue('max_advance_booking_days'))
      ->save();

    parent::submitForm($form, $form_state);

    $this->messenger()->addMessage($this->t('Settings saved successfully.'));
  }

}
