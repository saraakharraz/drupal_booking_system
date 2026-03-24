<?php

namespace Drupal\appointment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class AppointmentUserController extends ControllerBase {

  /**
   * Show user's appointments list with modify/cancel options.
   */
  public function appointments() {
    $current_user = \Drupal::currentUser();
    $user_email = $current_user->getEmail();

    // If user is anonymous
    if (empty($user_email)) {
      return [
        '#markup' => '<div class="appointment-empty"><p>' . $this->t('Please login to view your appointments.') . '</p></div>',
      ];
    }

    // Load all appointments for this user
    $appointments = \Drupal::entityTypeManager()
      ->getStorage('appointment')
      ->loadByProperties([
        'customer_email' => $user_email,
      ]);

    if (empty($appointments)) {
      return [
        '#markup' => '<div class="appointment-empty"><p>' . $this->t('You have no appointments yet.') . '</p></div>',
      ];
    }

    // Sort by appointment_date descending
    usort($appointments, function ($a, $b) {
      return strtotime($b->getAppointmentDate()) - strtotime($a->getAppointmentDate());
    });

    $html = '<div class="appointment-user-list">';

    foreach ($appointments as $appointment) {
      $appt_date = new \DateTime($appointment->getAppointmentDate());
      $date_formatted = $appt_date->format('d/m/Y');
      $time_start = $appt_date->format('H:i');
      $time_end = $appt_date->modify('+30 minutes')->format('H:i');

      // Load related entities
      $agency = \Drupal::entityTypeManager()->getStorage('agency')->load($appointment->get('agency')->target_id);
      $adviser = \Drupal::entityTypeManager()->getStorage('user')->load($appointment->get('adviser')->target_id);
      $type = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($appointment->get('appointment_type')->target_id);

      $agency_name = $agency ? $agency->getName() : 'N/A';
      $adviser_name = $adviser ? $adviser->getDisplayName() : 'N/A';

      $status = $appointment->getStatus();
      $status_color = ($status === 'confirmed') ? 'green' : ($status === 'cancelled' ? 'red' : 'gray');

      // Build appointment card
      $html .= '<div class="appointment-card-wrapper">';
      $html .= '<div class="appointment-card">';

      // Left side - Info
      $html .= '<div class="appointment-card-left">';
      $html .= '<div class="appointment-icon">📅</div>';
      $html .= '</div>';

      // Middle side - Details
      $html .= '<div class="appointment-card-middle">';
      $html .= '<p class="appointment-date-time"><strong>Rendez-vous le ' . $date_formatted . ' à ' . $time_start . '</strong></p>';
      $html .= '<p class="appointment-adviser">Avec ' . htmlspecialchars($adviser_name) . '</p>';
      $html .= '<p class="appointment-agency">Agence de rendez-vous : ' . htmlspecialchars($agency_name) . '</p>';
      $html .= '<p class="appointment-type">Type de rendez-vous : ' . htmlspecialchars($type ? $type->getName() : 'N/A') . '</p>';
      $html .= '</div>';

      // Right side - Actions
      $html .= '<div class="appointment-card-right">';

      if ($status !== 'cancelled') {
        // Modify button
        $modify_url = Url::fromRoute('appointment.user.modify', ['appointment_id' => $appointment->id()]);
        $html .= '<a href="' . $modify_url->toString() . '" class="appointment-btn appointment-btn-modify">Modifier</a>';

        // Cancel button
        $cancel_url = Url::fromRoute('appointment.user.cancel', ['appointment_id' => $appointment->id()]);
        $html .= '<a href="' . $cancel_url->toString() . '" class="appointment-btn appointment-btn-cancel">Supprimer</a>';
      }
      else {
        $html .= '<span class="appointment-disabled">Action non disponible</span>';
      }

      // Status
      $html .= '<p class="appointment-status" style="color: ' . $status_color . '; font-weight: bold; margin-top: 10px;">' . ucfirst($status) . '</p>';

      $html .= '</div>'; // appointment-card-right
      $html .= '</div>'; // appointment-card
      $html .= '</div>'; // appointment-card-wrapper
    }

    $html .= '</div>';

    return [
      '#markup' => $html,
      '#attached' => [
        'library' => ['appointment/appointment.theme'],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Modify appointment form.
   */
  public function modify($appointment_id) {
    $appointment = \Drupal::entityTypeManager()->getStorage('appointment')->load($appointment_id);

    if (!$appointment) {
      $this->messenger()->addError($this->t('Appointment not found.'));
      return $this->redirect('appointment.user.appointments');
    }

    $request = \Drupal::request();
    if ($request->getMethod() === 'POST') {
      $phone = $request->request->get('phone');

      // Vérifier le téléphone
      if ($phone === $appointment->getCustomerPhone()) {
        // Rediriger vers le formulaire utilisateur (pas admin)
        return $this->redirect('appointment.user.edit', ['appointment' => $appointment_id]);
      }
      else {
        $this->messenger()->addError($this->t('Incorrect phone number.'));
      }
    }

    return [
      '#type' => 'inline_template',
      '#template' => '
    <div class="appointment-modify-container">
      <h1>{{ title }}</h1>
      <form method="POST">
        <div class="form-group">
          <input type="tel" name="phone" placeholder="{{ phone }}" required>
        </div>
        <button type="submit" class="btn btn-primary">Valider</button>
      </form>
    </div>',
      '#context' => [
        'title' => $this->t('Modify Your Appointment'),
        'phone' => $appointment->getCustomerPhone(),
      ],
      '#attached' => ['library' => ['appointment/appointment.theme']],
    ];
  }

  /**
   * Cancel appointment form.
   */
  public function cancel($appointment_id) {
    $appointment = \Drupal::entityTypeManager()
      ->getStorage('appointment')
      ->load($appointment_id);

    if (!$appointment) {
      $this->messenger()->addError($this->t('Appointment not found.'));
      return $this->redirect('appointment.user.appointments');
    }

    $request = \Drupal::request();

    if ($request->getMethod() === 'POST') {
      $phone = $request->request->get('phone');

      if ($phone === $appointment->getCustomerPhone()) {

        // Mettre à jour le statut directement
        $appointment->set('status', 'cancelled');
        $appointment->save();

        // Envoi de l'email de cancellation via hook_mail()
        $params = [
          'appointment_id' => $appointment->id(),
          'customer_name' => $appointment->get('customer_name')->value,
          'customer_email' => $appointment->get('customer_email')->value,
          'appointment_date' => $appointment->get('appointment_date')->value,
          'customer_phone' => $appointment->get('customer_phone')->value,
          'agency' => $appointment->get('agency')->target_id,
          'adviser' => $appointment->get('adviser')->target_id,
          'type' => $appointment->get('appointment_type')->target_id,
        ];

        $mailManager = \Drupal::service('plugin.manager.mail');
        $result = $mailManager->mail(
          'appointment',                  // module
          'appointment_cancellation',     // key pour hook_mail
          $params['customer_email'],      // destinataire
          \Drupal::currentUser()->getPreferredLangcode(),
          $params,
          NULL,
          TRUE
        );

        if ($result['result'] !== TRUE) {
          \Drupal::logger('appointment')->error('Failed to send cancellation email to @email', ['@email' => $params['customer_email']]);
        }

        $this->messenger()->addStatus($this->t('Appointment cancelled successfully.'));

        return $this->redirect('appointment.user.appointments');
      }
      else {
        $this->messenger()->addError($this->t('Incorrect phone number.'));
      }
    }

    return [
      '#type' => 'inline_template',
      '#template' => '
      <div class="appointment-cancel-container">
        <h1>{{ title }}</h1>
        <form method="POST">
          <div class="form-group">
            <input type="tel" name="phone" placeholder="{{ phone }}" required>
          </div>
          <button type="submit" class="btn btn-danger">
            {{ button }}
          </button>
        </form>
      </div>
    ',
      '#context' => [
        'title' => $this->t('Cancel Your Appointment'),
        'phone' => $appointment->getCustomerPhone(),
        'button' => $this->t('Confirmer la suppression'),
      ],
      '#attached' => [
        'library' => ['appointment/appointment.theme'],
      ],
    ];
  }

}
