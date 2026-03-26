<?php

namespace Drupal\appointment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\appointment\Service\AppointmentManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Language\LanguageInterface;

class AppointmentUserEditForm extends FormBase {

  protected AppointmentManagerService $appointmentManager;
  protected EmailService $emailService;
  protected ?int $appointmentId = NULL;
  protected $appointmentEntity;

  public function __construct(AppointmentManagerService $appointmentManager) {
    $this->appointmentManager = $appointmentManager;

  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('appointment.manager')
    );
  }

  public function getFormId(): string {
    return 'appointment_user_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $appointment_id = NULL) {
    $this->appointmentId = $appointment_id;
    $this->appointmentEntity = $this->appointmentManager->getAppointmentById((int) $appointment_id);

    if (!$this->appointmentEntity) {
      $this->messenger()->addError($this->t('Appointment not found.'));
      return [];
    }

    if ($this->appointmentEntity->getStatus() === 'cancelled') {
      $this->messenger()->addWarning($this->t('This appointment is cancelled and cannot be modified.'));
      return [];
    }

    // Convert existing date to DrupalDateTime for the datetime form element
    $default_datetime = NULL;
    if ($this->appointmentEntity->get('appointment_date')->value) {
      try {
        $default_datetime = new DrupalDateTime($this->appointmentEntity->get('appointment_date')->value);
      } catch (\Exception $e) {
        $default_datetime = NULL;
      }
    }

    $form['appointment_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Appointment Date & Time'),
      '#default_value' => $default_datetime,
      '#required' => TRUE,
    ];

    $form['customer_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone Number'),
      '#default_value' => $this->appointmentEntity->get('customer_phone')->value,
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update Appointment'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $appointment_date_value = $form_state->getValue('appointment_date');
    if ($appointment_date_value instanceof DrupalDateTime) {
      $appointment_date_value = $appointment_date_value->format('Y-m-d H:i:s');
    }

    $values = [
      'appointment_date' => $appointment_date_value,
      'customer_phone' => $form_state->getValue('customer_phone'),
    ];

    try {
      // Update the appointment
      $this->appointmentManager->update($this->appointmentEntity, $values);

      $params = [
        'appointment_id' => $this->appointmentEntity->id(),
        'customer_name' => $this->appointmentEntity->get('customer_name')->value,
        'customer_email' => $this->appointmentEntity->get('customer_email')->value,
        'appointment_date' => $this->appointmentEntity->get('appointment_date')->value,
        'customer_phone' => $this->appointmentEntity->get('customer_phone')->value,
        'agency' => $this->appointmentEntity->get('agency')->target_id,
        'adviser' => $this->appointmentEntity->get('adviser')->target_id,
        'type' => $this->appointmentEntity->get('appointment_type')->target_id,
      ];
      // Send modification email
      $mailManager = \Drupal::service('plugin.manager.mail');

      $result = $mailManager->mail(
        'appointment',
        'appointment_modification',
        $params['customer_email'],
        LanguageInterface::LANGCODE_DEFAULT,
        $params,
        NULL,
        TRUE
      );

      if ($result['result'] !== TRUE) {
        \Drupal::logger('appointment')->error('Failed to send modification email to @email', ['@email' => $params['customer_email']]);
      }

      $this->messenger()->addStatus($this->t('Appointment updated successfully. A confirmation email has been sent.'));
      $form_state->setRedirect('appointment.user.appointments');
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error updating appointment: @msg', ['@msg' => $e->getMessage()]));
    }
  }
}
