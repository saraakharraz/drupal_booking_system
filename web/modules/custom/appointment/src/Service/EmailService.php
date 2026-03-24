<?php
//
//namespace Drupal\appointment\Service;
//
//use Drupal\appointment\Entity\AppointmentEntity;
//use Drupal\Core\Entity\EntityTypeManagerInterface;
//use Drupal\Core\Mail\MailManagerInterface;
//use Drupal\Core\Logger\LoggerChannelFactoryInterface;
//use Drupal\Core\Logger\LoggerChannel;
//use Drupal\user\Entity\User;
//
///**
// * Service for sending appointment-related emails via SMTP.
// */
//class EmailService {
//
//  protected EntityTypeManagerInterface $entityTypeManager;
//  protected MailManagerInterface $mailManager;
//  protected LoggerChannel $logger;
//
//  public function __construct(
//    EntityTypeManagerInterface $entityTypeManager,
//    LoggerChannelFactoryInterface $loggerFactory,
//    MailManagerInterface $mailManager
//  ) {
//    $this->entityTypeManager = $entityTypeManager;
//    $this->logger = $loggerFactory->get('appointment'); // LoggerChannel
//    $this->mailManager = $mailManager;
//  }
//
//  /**
//   * Send appointment confirmation email.
//   */
//  public function sendConfirmationEmail(AppointmentEntity $appointment): bool {
//    $subject = "Appointment Confirmation";
//    $body = $this->buildEmailBody($appointment, 'confirmed');
//
//    return $this->sendEmail($appointment->getCustomerEmail(), $subject, $body);
//  }
//
//  /**
//   * Send appointment modification email.
//   */
//  public function sendModificationEmail(AppointmentEntity $appointment): bool {
//    $subject = "Appointment Modified";
//    $body = $this->buildEmailBody($appointment, 'modified');
//
//    return $this->sendEmail($appointment->getCustomerEmail(), $subject, $body);
//  }
//
//  /**
//   * Send appointment cancellation email.
//   */
//  public function sendCancellationEmail(AppointmentEntity $appointment): bool {
//    $subject = "Appointment Cancelled";
//    $body = $this->buildEmailBody($appointment, 'cancelled');
//
//    return $this->sendEmail($appointment->getCustomerEmail(), $subject, $body);
//  }
//
//  /**
//   * Helper: build the email body.
//   */
//  protected function buildEmailBody(AppointmentEntity $appointment, string $type): string {
//    $customer_name = $appointment->getCustomerName();
//    $customer_phone = $appointment->getCustomerPhone();
//    $customer_address = $appointment->hasField('customer_address')
//      ? $appointment->get('customer_address')->value
//      : 'Not specified';
//
//    $appt_date_str = $appointment->getAppointmentDate();
//    $appt_date = \DateTime::createFromFormat('Y-m-d H:i:s', $appt_date_str);
//    $date_formatted = $appt_date ? $appt_date->format('l d F Y') : $appt_date_str;
//    $time_start = $appt_date ? $appt_date->format('H:i') : '';
//    $time_end = $appt_date ? $appt_date->modify('+30 minutes')->format('H:i') : '';
//
//    $agency = $appointment->hasField('agency')
//      ? $this->entityTypeManager->getStorage('agency')->load($appointment->get('agency')->target_id)
//      : NULL;
//    $adviser = $appointment->hasField('adviser')
//      ? User::load($appointment->get('adviser')->target_id)
//      : NULL;
//    $type_entity = $appointment->hasField('appointment_type')
//      ? $this->entityTypeManager->getStorage('taxonomy_term')->load($appointment->get('appointment_type')->target_id)
//      : NULL;
//
//    $agency_name = $agency ? $agency->getName() : 'N/A';
//    $adviser_name = $adviser ? $adviser->getDisplayName() : 'N/A';
//    $type_name = $type_entity ? $type_entity->getName() : 'N/A';
//
//    $message = "Hello $customer_name,\n\n";
//
//    switch ($type) {
//      case 'confirmed':
//        $message .= "Your appointment has been confirmed!\n\n";
//        break;
//      case 'modified':
//        $message .= "Your appointment has been modified!\n\n";
//        break;
//      case 'cancelled':
//        $message .= "Your appointment has been cancelled.\n\n";
//        break;
//    }
//
//    $message .= "Appointment Details:\n";
//    $message .= "Date: $date_formatted\n";
//    $message .= "Time: $time_start - $time_end\n";
//    $message .= "Adviser: $adviser_name\n";
//    $message .= "Agency: $agency_name\n";
//    $message .= "Type: $type_name\n";
//    $message .= "Phone: $customer_phone\n";
//    $message .= "Address: $customer_address\n\n";
//    $message .= "This is an automated email. Please do not reply.";
//
//    return $message;
//  }
//
//  /**
//   * Helper: send email via MailManager.
//   */
//  protected function sendEmail(string $to, string $subject, string $body): bool {
//    try {
//      $result = $this->mailManager->mail(
//        'appointment',          // module
//        'direct_send',          // key (ignored)
//        $to,
//        \Drupal::currentUser()->getPreferredLangcode(),
//        [
//          'subject' => $subject,
//          'body' => $body,
//        ],
//        NULL,
//        TRUE // force immediate SMTP send
//      );
//
//      if (!empty($result['result'])) {
//        $this->logger->info('Email sent to @email', ['@email' => $to]);
//        return TRUE;
//      }
//      else {
//        $this->logger->error('Failed to send email to @email', ['@email' => $to]);
//        return FALSE;
//      }
//    }
//    catch (\Exception $e) {
//      $this->logger->error('Error sending email: @error', ['@error' => $e->getMessage()]);
//      return FALSE;
//    }
//  }
//
//}
