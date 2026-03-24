<?php

namespace Drupal\appointment\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Agency add/edit forms.
 */
class AgencyForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the parent form
    $form = parent::buildForm($form, $form_state);

    // Customize the submit button
    $form['actions']['submit']['#value'] = $this->t('Save Agency');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate agency name is not empty
    $name = $form_state->getValue('name');
    if (empty($name) || empty($name[0]['value'])) {
      $form_state->setErrorByName('name',
        $this->t('Agency name is required.')
      );
    }

    // Validate contact email
    $email = $form_state->getValue('contact_email');
    if (!empty($email) && is_array($email)) {
      $email_value = $email[0]['value'];
      if (!filter_var($email_value, FILTER_VALIDATE_EMAIL)) {
        $form_state->setErrorByName('contact_email',
          $this->t('Invalid email address.')
        );
      }
    }

    // Validate contact phone format
    $phone = $form_state->getValue('contact_phone');
    if (!empty($phone) && is_array($phone)) {
      $phone_value = $phone[0]['value'];
      if (!preg_match('/^[0-9\-\+\s\(\)]+$/', $phone_value)) {
        $form_state->setErrorByName('contact_phone',
          $this->t('Invalid phone format.')
        );
      }
    }

    // ==================== VALIDATE OPERATING HOURS ====================
    $this->validateOperatingHours($form_state);
  }

  /**
   * Validate operating hours format.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  private function validateOperatingHours(FormStateInterface $form_state) {
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    foreach ($days as $day) {
      $open = $form_state->getValue("{$day}_open");
      $close = $form_state->getValue("{$day}_close");

      // Extract values from form state (they are arrays)
      $open_value = !empty($open) && is_array($open) ? $open[0]['value'] : $open;
      $close_value = !empty($close) && is_array($close) ? $close[0]['value'] : $close;

      // If one is set, the other must be set too
      if ((empty($open_value) && !empty($close_value)) || (!empty($open_value) && empty($close_value))) {
        $form_state->setErrorByName("{$day}_open",
          $this->t('Both opening and closing times must be set for @day, or leave both empty.',
            ['@day' => ucfirst($day)]
          )
        );
      }

      // Validate time format if both are set
      if (!empty($open_value) && !empty($close_value)) {
        // Validate opening time format
        if (!$this->isValidTimeFormat($open_value)) {
          $form_state->setErrorByName("{$day}_open",
            $this->t('Invalid time format for @day opening. Use HH:MM format (e.g., 08:30).',
              ['@day' => ucfirst($day)]
            )
          );
        }

        // Validate closing time format
        if (!$this->isValidTimeFormat($close_value)) {
          $form_state->setErrorByName("{$day}_close",
            $this->t('Invalid time format for @day closing. Use HH:MM format (e.g., 17:00).',
              ['@day' => ucfirst($day)]
            )
          );
        }

        // Validate that opening time is before closing time
        if ($this->isValidTimeFormat($open_value) && $this->isValidTimeFormat($close_value)) {
          $open_minutes = $this->timeToMinutes($open_value);
          $close_minutes = $this->timeToMinutes($close_value);

          if ($open_minutes >= $close_minutes) {
            $form_state->setErrorByName("{$day}_close",
              $this->t('Closing time must be after opening time for @day.',
                ['@day' => ucfirst($day)]
              )
            );
          }
        }
      }
    }
  }

  /**
   * Check if time format is valid (HH:MM).
   *
   * @param string $time
   *   The time string.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  private function isValidTimeFormat(string $time): bool {
    return (bool) preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $time);
  }

  /**
   * Convert time (HH:MM) to minutes.
   *
   * @param string $time
   *   The time string in HH:MM format.
   *
   * @return int
   *   Number of minutes since midnight.
   */
  private function timeToMinutes(string $time): int {
    [$hours, $minutes] = explode(':', $time);
    return (int) $hours * 60 + (int) $minutes;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Save the agency entity
    $status = parent::save($form, $form_state);

    $agency = $this->entity;

    // Log the action
    \Drupal::logger('appointment')->info(
      'Agency %name saved by user %uid',
      [
        '%name' => $agency->getName(),
        '%uid' => \Drupal::currentUser()->id(),
      ]
    );

    // Show message to user
    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage(
        $this->t('Agency %name has been created.',
          ['%name' => $agency->getName()]
        )
      );
    } else {
      $this->messenger()->addMessage(
        $this->t('Agency %name has been updated.',
          ['%name' => $agency->getName()]
        )
      );
    }

    // Redirect to agency list
    $form_state->setRedirect('entity.agency.collection');
  }

}
