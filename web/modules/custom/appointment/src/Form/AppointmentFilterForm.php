<?php

namespace Drupal\appointment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Filter form for appointments.
 */
class AppointmentFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'appointment_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();

    // Get current filter values from URL query parameters
    $adviser_id = $request->query->get('adviser');
    $agency_id = $request->query->get('agency');
    $date_from = $request->query->get('date_from');
    $date_to = $request->query->get('date_to');
    $status = $request->query->get('status');

    // Wrapper
    $form['#attributes']['class'] = ['appointment-filters'];

    // Adviser filter
    $form['adviser'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Adviser'),
      '#target_type' => 'user',
      '#required' => FALSE,
      '#default_value' => $adviser_id ? \Drupal::entityTypeManager()->getStorage('user')->load($adviser_id) : NULL,
      '#tags' => FALSE,
    ];

    // Agency filter - dropdown
    $agencies = \Drupal::entityTypeManager()
      ->getStorage('agency')
      ->loadMultiple();
    $agency_options = ['' => $this->t('- All Agencies -')];
    foreach ($agencies as $agency) {
      $agency_options[$agency->id()] = $agency->getName();
    }

    $form['agency'] = [
      '#type' => 'select',
      '#title' => $this->t('Agency'),
      '#options' => $agency_options,
      '#default_value' => $agency_id ?: '',
    ];

    // Date range filter
    $form['date_from'] = [
      '#type' => 'date',
      '#title' => $this->t('From Date'),
      '#required' => FALSE,
      '#default_value' => $date_from ?: '',
    ];

    $form['date_to'] = [
      '#type' => 'date',
      '#title' => $this->t('To Date'),
      '#required' => FALSE,
      '#default_value' => $date_to ?: '',
    ];

    // Status filter
    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => [
        '' => $this->t('- All Status -'),
        'pending' => $this->t('Pending'),
        'confirmed' => $this->t('Confirmed'),
        'cancelled' => $this->t('Cancelled'),
      ],
      '#default_value' => $status ?: '',
    ];

    // Buttons
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply Filters'),
      '#button_type' => 'primary',
    ];

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset Filters'),
      '#submit' => ['::resetFilters'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Build query parameters
    $query = [];

    if (!empty($values['adviser'])) {
      $query['adviser'] = $values['adviser'];
    }

    if (!empty($values['agency'])) {
      $query['agency'] = $values['agency'];
    }

    if (!empty($values['date_from'])) {
      $query['date_from'] = $values['date_from'];
    }

    if (!empty($values['date_to'])) {
      $query['date_to'] = $values['date_to'];
    }

    if (!empty($values['status'])) {
      $query['status'] = $values['status'];
    }

    // Redirect with query parameters
    $url = Url::fromRoute('entity.appointment.collection', [], ['query' => $query]);
    $form_state->setRedirectUrl($url);
  }

  /**
   * Reset filters.
   */
  public function resetFilters(array &$form, FormStateInterface $form_state) {
    // Redirect to collection without query parameters
    $url = Url::fromRoute('entity.appointment.collection');
    $form_state->setRedirectUrl($url);
  }

}
