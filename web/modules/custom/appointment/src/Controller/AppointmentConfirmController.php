<?php

namespace Drupal\appointment\Controller;

use Drupal\Core\Controller\ControllerBase;

class AppointmentConfirmController extends ControllerBase {

  public function confirm() {
    $current_user = \Drupal::currentUser();
    $user_email = $current_user->getEmail();

    // If user is anonymous, show message
    if (empty($user_email)) {
      return [
        '#markup' => '<div class="appointment-confirm">' .
          '<h1>' . $this->t('✓ Your Appointment is Confirmed!') . '</h1>' .
          '<p>' . $this->t('A confirmation email has been sent to your email address.') . '</p>' .
          '</div>',
      ];
    }


    if (empty($appointments)) {
      return [
        '#markup' => '<div class="appointment-confirm">' .
          '<h1>' . $this->t('✓ Your Appointment is Confirmed!') . '</h1>' .
          '<p>' . $this->t('A confirmation email has been sent to your email address.') . '</p>' .
          '</div>',
      ];
    }


    $html = '<div class="appointment-list-container">';
    $html .= '<h1>' . $this->t('✓ Your Appointment is Confirmed!') . '</h1>';

    $html .= '</div>';
    return ['#markup' => $html];
  }

}
