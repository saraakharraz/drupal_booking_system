<?php

namespace Drupal\appointment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Url;

class AnonymousEmailForm extends FormBase {

  public function getFormId() {
    return 'appointment_anonymous_email_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send me my appointments link'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');

    // Generate token
    $token = Crypt::randomBytesBase64(32);
    $now = time(); // current timestamp
    $expires = strtotime('+24 hours');


    \Drupal::database()->insert('appointment_access_token')
      ->fields([
        'token' => $token,
        'email' => $email,
        'created' => $now,
        'expires' => $expires,
      ])->execute();

    // Generate the absolute link with token
    $link = Url::fromRoute('appointment.user.appointments', [], [
      'query' => ['token' => $token],  // adds ?token=XYZ
      'absolute' => TRUE,               // full URL
    ])->toString();

// Send email
    \Drupal::service('plugin.manager.mail')->mail(
      'appointment',                 // module name
      'access_link',                 // key defined in hook_mail()
      $email,                        // recipient
      \Drupal::currentUser()->getPreferredLangcode() ?: LanguageInterface::LANGCODE_DEFAULT,
      ['link' => $link],             // parameters for email template
      NULL,                           // from address (NULL = default)
      TRUE                            // send immediately
    );
    $this->messenger()->addStatus($this->t('A link to view your appointments has been sent to your email.'));
  }
}
