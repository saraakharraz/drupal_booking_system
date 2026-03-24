<?php

namespace Drupal\appointment\Controller;

use Drupal\appointment\Entity\AppointmentEntity;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for Appointment entity routes.
 */
class AppointmentController extends ControllerBase {

  /**
   * Display a single appointment.
   */
//  public function view(AppointmentEntity $appointment): array {
//
//    return [
//      '#entity' => $appointment,
//    ];
//  }

  /**
   * Get title for a single appointment view.
   */
  public function getTitle(AppointmentEntity $appointment): string {
    // Use customer name or fallback
    return $appointment->get('customer_name')->value ?? $this->t('Appointment');
  }

  /**
   * Cancel an appointment.
   */
  public function cancel(AppointmentEntity $appointment): RedirectResponse {
    try {
      $current_user = $this->currentUser();

      // Check permissions
      if (
        !$current_user->hasPermission('administer appointments') &&
        !$current_user->hasPermission('cancel own appointment')
      ) {
        $this->messenger()->addError(
          $this->t('You do not have permission to cancel this appointment.')
        );
        return new RedirectResponse('/');
      }

      // Cancel via the manager service
      $manager = \Drupal::service('appointment.manager');
      $manager->cancel($appointment);

      $this->messenger()->addStatus(
        $this->t('Appointment %title has been cancelled.', [
          '%title' => $appointment->get('customer_name')->value ?? $appointment->getTitle(),
        ])
      );

      \Drupal::logger('appointment')->info(
        'Appointment %id cancelled by user %uid',
        [
          '%id' => $appointment->id(),
          '%uid' => $current_user->id(),
        ]
      );

    } catch (\Exception $e) {
      $this->messenger()->addError(
        $this->t('Error cancelling appointment: @error', [
          '@error' => $e->getMessage(),
        ])
      );
      \Drupal::logger('appointment')->error(
        'Error cancelling appointment: @error',
        ['@error' => $e->getMessage()]
      );
    }

    return new RedirectResponse('/admin/appointments');
  }

}
