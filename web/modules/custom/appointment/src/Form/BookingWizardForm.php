<?php

namespace Drupal\appointment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Multi-step booking wizard form.
 */
class BookingWizardForm extends FormBase {

  /**
   * Get temp store for wizard data.
   */
  protected function getTempStore() {
    return \Drupal::service('tempstore.private')->get('appointment_booking_wizard');
  }

  /**
   * Save data to temp store.
   */
  protected function saveTempData($key, $value) {
    $store = $this->getTempStore();
    $store->set($key, $value);
  }

  /**
   * Get data from temp store.
   */
  protected function getTempData($key, $default = NULL) {
    $store = $this->getTempStore();
    $data = $store->get($key);
    return $data ?? $default;
  }

  /**
   * Get all temp data.
   */
  protected function getAllTempData() {
    $store = $this->getTempStore();
    $all_data = [];

    $keys = [
      'agency',
      'type',
      'adviser',
      'appointment_date',
      'customer_name',
      'customer_email',
      'customer_phone',
      'customer_address',
    ];

    foreach ($keys as $key) {
      $value = $store->get($key);
      if ($value !== NULL) {
        $all_data[$key] = $value;
      }
    }

    return $all_data;
  }

  /**
   * Clear temp data.
   */
  protected function clearAllTempData() {
    $store = $this->getTempStore();

    $keys = [
      'agency',
      'type',
      'adviser',
      'appointment_date',
      'customer_name',
      'customer_email',
      'customer_phone',
      'customer_address',
    ];

    foreach ($keys as $key) {
      $store->delete($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'booking_wizard_form';
  }

  /**
   * Get current step from route.
   */
  protected function getCurrentStep() {
    $step = \Drupal::routeMatch()->getParameter('step');
    return $step ? (int)$step : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_step = $this->getCurrentStep();

    $form['progress'] = [
      '#theme' => 'appointment_booking_progress',
      '#current_step' => $current_step,
      '#total_steps' => 6,
    ];

    switch ($current_step) {
      case 1:
        $form = $this->buildStep1($form, $form_state);
        break;

      case 2:
        $form = $this->buildStep2($form, $form_state);
        break;

      case 3:
        $form = $this->buildStep3($form, $form_state);
        break;

      case 4:
        $form = $this->buildStep4($form, $form_state);
        break;

      case 5:
        $form = $this->buildStep5($form, $form_state);
        break;

      case 6:
        $form = $this->buildStep6($form, $form_state);
        break;
    }

    return $form;
  }

  /**
   * STEP 1: Select Agency.
   */
  protected function buildStep1(&$form, FormStateInterface $form_state) {
    $agencies = \Drupal::entityTypeManager()
      ->getStorage('agency')
      ->loadMultiple();

    $agency_options = [];
    foreach ($agencies as $agency) {
      $agency_options[$agency->id()] = $agency->getName();
    }

    $form['agency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select an Agency'),
      '#options' => $agency_options,
      '#required' => TRUE,
      '#default_value' => $this->getTempData('agency'),
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next Step →'),
      '#button_type' => 'primary',
      '#submit' => ['::submitStep1'],
    ];

    return $form;
  }

  /**
   * STEP 2: Select Appointment Type.
   */
  protected function buildStep2(&$form, FormStateInterface $form_state) {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'appointment_type']);

    $type_options = [];
    foreach ($terms as $term) {
      $type_options[$term->id()] = $term->getName();
    }

    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select Appointment Type'),
      '#options' => $type_options,
      '#required' => TRUE,
      '#default_value' => $this->getTempData('type'),
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('← Back'),
      '#submit' => ['::goBack'],
    ];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next Step →'),
      '#button_type' => 'primary',
      '#submit' => ['::submitStep2'],
    ];

    return $form;
  }

  /**
   * STEP 3: Select Adviser (FILTERED by agency + appointment type).
   */
  protected function buildStep3(&$form, FormStateInterface $form_state) {
    $agency_id = $this->getTempData('agency');
    $type_id = $this->getTempData('type');

    if (!$agency_id || !$type_id) {
      $this->messenger()->addError($this->t('Please complete the previous steps.'));
      return $form;
    }

    $filtered_advisers = $this->getFilteredAdvisers($agency_id, $type_id);

    if (empty($filtered_advisers)) {
      $this->messenger()->addWarning($this->t('No advisers available for this selection.'));
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('← Back'),
        '#submit' => ['::goBack'],
      ];
      return $form;
    }

    $form['adviser'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select an Adviser'),
      '#options' => $filtered_advisers,
      '#required' => TRUE,
      '#default_value' => $this->getTempData('adviser'),
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('← Back'),
      '#submit' => ['::goBack'],
    ];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next Step →'),
      '#button_type' => 'primary',
      '#submit' => ['::submitStep3'],
    ];

    return $form;
  }

  /**
   * STEP 4: Select Date & Time with FullCalendar.
   */
  protected function buildStep4(&$form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'appointment/appointment.theme';

    $form['#attached']['drupalSettings']['appointment']['default_appointment_duration'] = $this->config('appointment.settings')->get('default_appointment_duration') ?? 30;

    $agency_id = $this->getTempData('agency');
    $adviser_id = $this->getTempData('adviser');

    // Passer agency_id et adviser_id au JS via drupalSettings
    $form['#attached']['drupalSettings']['appointment']['agency_id']  = $agency_id;
    $form['#attached']['drupalSettings']['appointment']['adviser_id'] = $adviser_id;


    if ($agency_id && $adviser_id) {
      $agency = \Drupal::entityTypeManager()->getStorage('agency')->load($agency_id);
      $adviser = \Drupal::entityTypeManager()->getStorage('user')->load($adviser_id);


      // Get working days and hours from the fields
      $working_days  = $adviser->get('field_adviser_working_days')->value ?? '';
      $working_hours = $adviser->get('field_adviser_working_hours')->value ?? '';

      // Pass working days and hours to JS
      $form['#attached']['drupalSettings']['appointment']['adviser_working_days']  = $working_days;
      $form['#attached']['drupalSettings']['appointment']['adviser_working_hours'] = $working_hours;

      $form['info'] = [
        '#markup' => '<div class="appointment-info"><p><strong>Agency:</strong> ' . $agency->getName() . '</p>' .
          '<p><strong>Adviser:</strong> ' . $adviser->getDisplayName() . '</p></div>',
      ];
    }

    $form['calendar_heading'] = [
      '#markup' => '<h2>' . $this->t('CHOISISSEZ LE JOUR ET L\'HEURE DE VOTRE RENDEZ-VOUS') . '</h2>',
    ];

    $form['calendar_container'] = [
      '#markup' => '<div id="appointment-calendar"></div>',
    ];

    // FIX: Use #default_value instead of #value so it gets submitted
    $form['appointment_date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Appointment Date and Time'),
      '#default_value' => $this->getTempData('appointment_date'),
      '#attributes' => [
        'class' => ['appointment-date-hidden'],
        'style' => 'display: none !important;',
      ],
      '#required' => FALSE,
    ];

    $form['selected_datetime'] = [
      '#markup' => '<div id="selected-datetime" class="selected-datetime-display"></div>',
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('← Back'),
      '#submit' => ['::goBack'],
    ];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next Step →'),
      '#button_type' => 'primary',
      '#submit' => ['::submitStep4'],
    ];

    return $form;
  }

  /**
   * STEP 5: Personal Information.
   */
  protected function buildStep5(&$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();

    $form['customer_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
      '#default_value' => $this->getTempData('customer_name', $current_user->getDisplayName()),
    ];

    $form['customer_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
      '#default_value' => $this->getTempData('customer_email', $current_user->getEmail()),
    ];

    $form['customer_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone Number'),
      '#required' => TRUE,
      '#default_value' => $this->getTempData('customer_phone'),
    ];

    $form['customer_address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Address'),
      '#required' => FALSE,
      '#default_value' => $this->getTempData('customer_address'),
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('← Back'),
      '#submit' => ['::goBack'],
    ];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next Step →'),
      '#button_type' => 'primary',
      '#submit' => ['::submitStep5'],
    ];

    return $form;
  }

  /**
   * STEP 6: Confirmation Page.
   */
  protected function buildStep6(&$form, FormStateInterface $form_state) {
    $all_data = $this->getAllTempData();

    if (empty($all_data['agency']) || empty($all_data['adviser']) || empty($all_data['type']) ||
      empty($all_data['appointment_date']) || empty($all_data['customer_name']) || empty($all_data['customer_email'])) {
      $this->messenger()->addError($this->t('Error: Missing appointment data. Please start over.'));
      $form_state->setRedirect('appointment.booking.step', ['step' => 1]);
      return $form;
    }

    $agency = \Drupal::entityTypeManager()->getStorage('agency')->load($all_data['agency']);
    $adviser = \Drupal::entityTypeManager()->getStorage('user')->load($all_data['adviser']);
    $appointment_type = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($all_data['type']);

    if (!$agency || !$adviser || !$appointment_type) {
      $this->messenger()->addError($this->t('Error: Could not load appointment details.'));
      $form_state->setRedirect('appointment.booking.step', ['step' => 1]);
      return $form;
    }

    $appt_date = new \DateTime($all_data['appointment_date']);
    $date_formatted = $appt_date->format('l d F Y');
    $time_start = $appt_date->format('H:i');
    $time_end = $appt_date->modify('+30 minutes')->format('H:i');
    $time_formatted = $time_start . ' - ' . $time_end;

    $form['user_profile'] = [
      '#markup' => '<div class="confirmation-section"><h3>' . $this->t('Profil de l\'utilisateur') . '</h3>' .
        '<p><strong>Nom:</strong> ' . htmlspecialchars($all_data['customer_name']) . '</p>' .
        '<p><strong>Téléphone:</strong> ' . htmlspecialchars($all_data['customer_phone']) . '</p>' .
        '<p><strong>Email:</strong> ' . htmlspecialchars($all_data['customer_email']) . '</p></div>',
    ];

    $form['appointment_details'] = [
      '#markup' => '<div class="confirmation-section"><h3>' . $this->t('Rendez-vous') . '</h3>' .
        '<p><strong>Agency:</strong> ' . htmlspecialchars($agency->getName()) . '</p>' .
        '<p><strong>Adviser:</strong> ' . htmlspecialchars($adviser->getDisplayName()) . '</p>' .
        '<p><strong>Type:</strong> ' . htmlspecialchars($appointment_type->getName()) . '</p>' .
        '<p><strong>Date:</strong> ' . $date_formatted . '</p>' .
        '<p><strong>Time:</strong> ' . $time_formatted . '</p>' .
        '<p><strong>Address:</strong> ' . htmlspecialchars($all_data['customer_address'] ?? 'Not specified') . '</p></div>',
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('← Back'),
      '#submit' => ['::goBack'],
    ];

    $form['actions']['confirm'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirmer'),
      '#button_type' => 'primary',
      '#submit' => ['::submitStep6'],
    ];

    return $form;
  }

  /**
   * Get advisers filtered by agency and appointment type.
   */
  protected function getFilteredAdvisers($agency_id, $appointment_type_id) {
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadMultiple();

    $advisers = [];

    foreach ($users as $user) {
      if ($user->isAnonymous()) {
        continue;
      }

      $user_agency = $user->get('field_adviser_agency')->target_id;
      $user_specialization = $user->get('field_specializations')->target_id;

      $matches = ($user_agency == $agency_id && $user_specialization == $appointment_type_id);

      if ($matches) {
        $advisers[$user->id()] = $user->getDisplayName();
      }
    }

    return $advisers;
  }

  /**
   * Submit Step 1.
   */
  public function submitStep1(array &$form, FormStateInterface $form_state) {
    $this->saveTempData('agency', $form_state->getValue('agency'));
    $form_state->setRedirect('appointment.booking.step', ['step' => 2]);
  }

  /**
   * Submit Step 2.
   */
  public function submitStep2(array &$form, FormStateInterface $form_state) {
    $this->saveTempData('type', $form_state->getValue('type'));
    $form_state->setRedirect('appointment.booking.step', ['step' => 3]);
  }

  /**
   * Submit Step 3.
   */
  public function submitStep3(array &$form, FormStateInterface $form_state) {
    $this->saveTempData('adviser', $form_state->getValue('adviser'));
    $form_state->setRedirect('appointment.booking.step', ['step' => 4]);
  }

  /**
   * Submit Step 4.
   */
  public function submitStep4(array &$form, FormStateInterface $form_state) {
    $date = $form_state->getValue('appointment_date');
    $this->saveTempData('appointment_date', $date);
    $form_state->setRedirect('appointment.booking.step', ['step' => 5]);
  }

  /**
   * Submit Step 5.
   */
  public function submitStep5(array &$form, FormStateInterface $form_state) {
    $this->saveTempData('customer_name', $form_state->getValue('customer_name'));
    $this->saveTempData('customer_email', $form_state->getValue('customer_email'));
    $this->saveTempData('customer_phone', $form_state->getValue('customer_phone'));
    $this->saveTempData('customer_address', $form_state->getValue('customer_address'));
    $form_state->setRedirect('appointment.booking.step', ['step' => 6]);
  }

  /**
   * Submit Step 6 (Final Confirmation).
   */
  public function submitStep6(array &$form, FormStateInterface $form_state) {
    $all_data = $this->getAllTempData();

    if (empty($all_data['agency']) || empty($all_data['adviser']) || empty($all_data['type']) ||
      empty($all_data['appointment_date']) || empty($all_data['customer_name']) || empty($all_data['customer_email'])) {
      $this->messenger()->addError($this->t('Error: Missing required appointment data.'));
      return;
    }

    // Create appointment entity
    $appointment = \Drupal::entityTypeManager()->getStorage('appointment')->create([
      'title' => '',
      'appointment_date' => $all_data['appointment_date'],
      'agency' => $all_data['agency'],
      'adviser' => $all_data['adviser'],
      'appointment_type' => $all_data['type'],
      'customer_name' => $all_data['customer_name'],
      'customer_email' => $all_data['customer_email'],
      'customer_phone' => $all_data['customer_phone'],
      'customer_address' => $all_data['customer_address'] ?? '',
      'status' => 'pending',
    ]);

    try {
      $appointment->save();
      \Drupal::logger('appointment')->info('Appointment created - ID: @id', [
        '@id' => $appointment->id(),
      ]);
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error creating appointment.'));
      return;
    }

    // Send confirmation email using EmailService
    $params = $all_data;
    \Drupal::service('plugin.manager.mail')->mail('appointment','appointment_pending',$all_data['customer_email'],'en',$params);


    // Clear temp data
    $this->clearAllTempData();

    $this->messenger()->addStatus($this->t('✓ Appointment booked! Check your email for confirmation.'));
    $form_state->setRedirect('appointment.booking.confirm');
  }
//  /**
//   * Send confirmation email.
//   */
//  protected function sendConfirmationEmail($all_data) {
//    try {
//      $mailManager = \Drupal::service('plugin.manager.mail');
//
//      $params = [
//        'appointment_date' => $all_data['appointment_date'],
//        'customer_name' => $all_data['customer_name'],
//        'customer_email' => $all_data['customer_email'],
//        'customer_phone' => $all_data['customer_phone'],
//        'customer_address' => $all_data['customer_address'] ?? '',
//        'agency_id' => $all_data['agency'],
//        'adviser_id' => $all_data['adviser'],
//        'type_id' => $all_data['type'],
//      ];
//
//      $mailManager->mail(
//        'appointment',
//        'appointment_confirmation',
//        $all_data['customer_email'],
//        'en',
//        $params
//      );
//    } catch (\Exception $e) {
//      \Drupal::logger('appointment')->error('Error sending confirmation email: @error', [
//        '@error' => $e->getMessage(),
//      ]);
//    }
//  }

  /**
   * Go back to previous step.
   */
  public function goBack(array &$form, FormStateInterface $form_state) {
    $current_step = $this->getCurrentStep();
    $previous_step = max(1, $current_step - 1);
    $form_state->setRedirect('appointment.booking.step', ['step' => $previous_step]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This is not called directly
  }

}
