<?php

namespace Drupal\appointment\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Form controller for Appointment add/edit forms.
 *
 * This form handles both creating new appointments
 * and editing existing ones.
 */
class AppointmentSubmitForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the parent form (handles all entity fields automatically)
    $form = parent::buildForm($form, $form_state);

    // Get the appointment entity
    $appointment = $this->entity;

    // Add custom validation and submit handlers
    $form['actions']['submit']['#value'] = $this->t('Save Appointment');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Get form values
    $appointment_date = $form_state->getValue('appointment_date');
    $adviser_id = $form_state->getValue('adviser');
    $agency_id = $form_state->getValue('agency');

    // Validate appointment date is in the future
    if (!empty($appointment_date)) {
      $date_value = $appointment_date[0]['value'];
      if (!empty($date_value)) {
        $appointment_timestamp = strtotime($date_value);
        $now = time();

        if ($appointment_timestamp <= $now) {
          $form_state->setErrorByName('appointment_date',
            $this->t('Appointment date must be in the future.')
          );
        }
      }
    }

    // Validate agency exists
    if (!empty($agency_id) && is_array($agency_id)) {
      $agency_target = $agency_id[0]['target_id'];
      $agency_storage = \Drupal::entityTypeManager()->getStorage('agency');
      $agency = $agency_storage->load($agency_target);

      if (!$agency) {
        $form_state->setErrorByName('agency',
          $this->t('Selected agency does not exist.')
        );
      }
    } else {
      $form_state->setErrorByName('agency',
        $this->t('Agency is required.')
      );
    }

    // Validate adviser exists
    if (!empty($adviser_id) && is_array($adviser_id)) {
      $adviser_target = $adviser_id[0]['target_id'];
      $adviser = User::load($adviser_target);

      if (!$adviser) {
        $form_state->setErrorByName('adviser',
          $this->t('Selected adviser does not exist.')
        );
      }
    } else {
      $form_state->setErrorByName('adviser',
        $this->t('Adviser is required.')
      );
    }

    // Validate customer email format
    $customer_email = $form_state->getValue('customer_email');
    if (!empty($customer_email) && is_array($customer_email)) {
      $email = $customer_email[0]['value'];
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_state->setErrorByName('customer_email',
          $this->t('Invalid email address.')
        );
      }
    }

    // Validate customer phone format
    $customer_phone = $form_state->getValue('customer_phone');
    if (!empty($customer_phone) && is_array($customer_phone)) {
      $phone = $customer_phone[0]['value'];
      if (!preg_match('/^[0-9\-\+\s\(\)]+$/', $phone)) {
        $form_state->setErrorByName('customer_phone',
          $this->t('Invalid phone format.')
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Save the appointment entity
    $status = parent::save($form, $form_state);

    $appointment = $this->entity;

    // Log the action
    \Drupal::logger('appointment')->info(
      'Appointment %id saved by user %uid',
      [
        '%id' => $appointment->id(),
        '%uid' => \Drupal::currentUser()->id(),
      ]
    );

    // Show message to user
    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage(
        $this->t('Appointment %title has been created.',
          ['%title' => $appointment->getTitle()]
        )
      );
    } else {
      $this->messenger()->addMessage(
        $this->t('Appointment %title has been updated.',
          ['%title' => $appointment->getTitle()]
        )
      );
    }

    // Redirect to appointment list
    $form_state->setRedirect('entity.appointment.collection');
  }

}
