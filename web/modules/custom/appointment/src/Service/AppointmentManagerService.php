<?php

namespace Drupal\appointment\Service;

use Drupal\appointment\Entity\AppointmentEntity;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for managing appointment operations.
 *
 * Handles:
 * - Creating appointments
 * - Updating appointments
 * - Deleting appointments
 * - Retrieving appointments
 * - Checking for double bookings
 */
class AppointmentManagerService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;



  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('appointment');

  }

  /**
   * Create a new appointment.
   *
   * @param array $values
   *   Array of appointment field values.
   *   Required keys: title, appointment_date, agency, adviser, appointment_type,
   *                 customer_name, customer_email, customer_phone
   *
   * @return \Drupal\appointment\Entity\AppointmentEntity|null
   *   The created appointment entity, or NULL on failure.
   *
   * @throws \Exception
   *   If validation fails.
   */
  public function create(array $values): ?AppointmentEntity {
    try {
      // Validate required fields
      $required_fields = [
        'title',
        'appointment_date',
        'agency',
        'adviser',
        'appointment_type',
        'customer_name',
        'customer_email',
        'customer_phone',
      ];

      foreach ($required_fields as $field) {
        if (empty($values[$field])) {
          throw new \Exception("Required field missing: {$field}");
        }
      }

      // Validate email
      if (!filter_var($values['customer_email'], FILTER_VALIDATE_EMAIL)) {
        throw new \Exception('Invalid email address');
      }

      // Check for double booking
      if ($this->isDoubleBooked($values['adviser'], $values['appointment_date'])) {
        throw new \Exception('Adviser is already booked for this time slot');
      }

      // Create the appointment entity
      $storage = $this->entityTypeManager->getStorage('appointment');
      $appointment = $storage->create($values);

      // Set default status
      if (empty($appointment->getStatus())) {
        $appointment->setStatus('pending');
      }

      // Save the appointment
      $appointment->save();

      // Log the creation
      $this->logger->info(
        'Appointment %id created by user %uid',
        [
          '%id' => $appointment->id(),
          '%uid' => \Drupal::currentUser()->id(),
        ]
      );

      return $appointment;

    } catch (\Exception $e) {
      $this->logger->error(
        'Error creating appointment: @error',
        ['@error' => $e->getMessage()]
      );
      throw $e;
    }
  }

  /**
   * Update an existing appointment.
   *
   * @param \Drupal\appointment\Entity\AppointmentEntity $appointment
   *   The appointment entity to update.
   * @param array $values
   *   Array of field values to update.
   *
   * @return \Drupal\appointment\Entity\AppointmentEntity|null
   *   The updated appointment entity, or NULL on failure.
   */
  public function update(AppointmentEntity $appointment, array $values): ?AppointmentEntity {
    try {
      // If appointment date is being changed, check for double booking
      if (!empty($values['appointment_date'])) {
        if ($this->isDoubleBooked(
          $values['adviser'] ?? $appointment->getAdviserId(),
          $values['appointment_date'],
          $appointment->id()
        )) {
          throw new \Exception('Adviser is already booked for this time slot');
        }
      }

      // Update fields
      foreach ($values as $field => $value) {
        if (!empty($value)) {
          $appointment->set($field, $value);
        }
      }

      // Save the updated appointment
      $appointment->save();

      // Log the update
      $this->logger->info(
        'Appointment %id updated by user %uid',
        [
          '%id' => $appointment->id(),
          '%uid' => \Drupal::currentUser()->id(),
        ]
      );

      return $appointment;

    } catch (\Exception $e) {
      $this->logger->error(
        'Error updating appointment %id: @error',
        [
          '%id' => $appointment->id(),
          '@error' => $e->getMessage(),
        ]
      );
      throw $e;
    }
  }

  /**
   * Delete an appointment.
   *
   * @param \Drupal\appointment\Entity\AppointmentEntity $appointment
   *   The appointment entity to delete.
   *
   * @return bool
   *   TRUE if deleted successfully, FALSE otherwise.
   */
  public function delete(AppointmentEntity $appointment): bool {
    try {
      $appointment_id = $appointment->id();
      $title = $appointment->getTitle();

      // Delete the appointment
      $appointment->delete();

      // Log the deletion
      $this->logger->info(
        'Appointment %id (%title) deleted by user %uid',
        [
          '%id' => $appointment_id,
          '%title' => $title,
          '%uid' => \Drupal::currentUser()->id(),
        ]
      );

      return TRUE;

    } catch (\Exception $e) {
      $this->logger->error(
        'Error deleting appointment: @error',
        ['@error' => $e->getMessage()]
      );
      return FALSE;
    }
  }

  /**
   * Cancel an appointment (set status to cancelled).
   *
   * @param \Drupal\appointment\Entity\AppointmentEntity $appointment
   *   The appointment entity to cancel.
   *
   * @return \Drupal\appointment\Entity\AppointmentEntity|null
   *   The cancelled appointment entity, or NULL on failure.
   */
  public function cancel(AppointmentEntity $appointment): ?AppointmentEntity {
    try {
      // Check if appointment is already cancelled
      if ($appointment->getStatus() === 'cancelled') {
        throw new \Exception('Appointment is already cancelled');
      }

      // Set status to cancelled
      $appointment->setStatus('cancelled');

      // Save the appointment
      $appointment->save();

      // Log the cancellation
      $this->logger->info(
        'Appointment %id cancelled by user %uid',
        [
          '%id' => $appointment->id(),
          '%uid' => \Drupal::currentUser()->id(),
        ]
      );

      return $appointment;

    } catch (\Exception $e) {
      $this->logger->error(
        'Error cancelling appointment %id: @error',
        [
          '%id' => $appointment->id(),
          '@error' => $e->getMessage(),
        ]
      );
      throw $e;
    }
  }

  /**
   * Get appointment by ID.
   *
   * @param int $appointment_id
   *   The appointment ID.
   *
   * @return \Drupal\appointment\Entity\AppointmentEntity|null
   *   The appointment entity, or NULL if not found.
   */
  public function getAppointmentById(int $appointment_id): ?AppointmentEntity {
    $storage = $this->entityTypeManager->getStorage('appointment');
    return $storage->load($appointment_id);
  }

  /**
   * Get all appointments for a customer.
   *
   * @param string $customer_email
   *   The customer email address.
   *
   * @return array
   *   Array of appointment entities.
   */
  public function getAppointmentsByCustomer(string $customer_email): array {
    $storage = $this->entityTypeManager->getStorage('appointment');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('customer_email', $customer_email)
      ->sort('appointment_date', 'DESC');

    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Get all appointments for an adviser.
   *
   * @param int $adviser_id
   *   The adviser (user) ID.
   *
   * @return array
   *   Array of appointment entities.
   */
  public function getAppointmentsByAdviser(int $adviser_id): array {
    $storage = $this->entityTypeManager->getStorage('appointment');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('adviser', $adviser_id)
      ->sort('appointment_date', 'DESC');

    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Get all appointments for an agency.
   *
   * @param int $agency_id
   *   The agency ID.
   *
   * @return array
   *   Array of appointment entities.
   */
  public function getAppointmentsByAgency(int $agency_id): array {
    $storage = $this->entityTypeManager->getStorage('appointment');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('agency', $agency_id)
      ->sort('appointment_date', 'DESC');

    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Check if an adviser has a double booking.
   *
   * @param int $adviser_id
   *   The adviser ID.
   * @param string $appointment_date
   *   The appointment date/time.
   * @param int|null $exclude_appointment_id
   *   Optional: appointment ID to exclude from check (for updates).
   *
   * @return bool
   *   TRUE if double booked, FALSE otherwise.
   */
  public function isDoubleBooked(int $adviser_id, string $appointment_date, ?int $exclude_appointment_id = NULL): bool {
    try {
      // Parse the appointment date
      $appointment_time = strtotime($appointment_date);
      if ($appointment_time === FALSE) {
        return FALSE;
      }

      // Get all appointments for this adviser
      $appointments = $this->getAppointmentsByAdviser($adviser_id);

      // Check for conflicts (same date/time, excluding cancelled)
      foreach ($appointments as $appointment) {
        // Skip the appointment being updated
        if ($exclude_appointment_id && $appointment->id() === $exclude_appointment_id) {
          continue;
        }

        // Skip cancelled appointments
        if ($appointment->getStatus() === 'cancelled') {
          continue;
        }

        // Check if dates conflict
        $existing_time = strtotime($appointment->getAppointmentDate());
        if ($existing_time === $appointment_time) {
          return TRUE;
        }
      }

      return FALSE;

    } catch (\Exception $e) {
      $this->logger->error(
        'Error checking double booking: @error',
        ['@error' => $e->getMessage()]
      );
      return FALSE;
    }
  }

  /**
   * Get appointments by date range.
   *
   * @param string $start_date
   *   Start date (format: YYYY-MM-DD).
   * @param string $end_date
   *   End date (format: YYYY-MM-DD).
   *
   * @return array
   *   Array of appointment entities.
   */
  public function getAppointmentsByDateRange(string $start_date, string $end_date): array {
    $storage = $this->entityTypeManager->getStorage('appointment');

    $start_timestamp = strtotime($start_date . ' 00:00:00');
    $end_timestamp = strtotime($end_date . ' 23:59:59');

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('appointment_date', $start_timestamp, '>=')
      ->condition('appointment_date', $end_timestamp, '<=')
      ->sort('appointment_date', 'ASC');

    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Get upcoming appointments (not cancelled, in the future).
   *
   * @param int $limit
   *   Number of appointments to retrieve.
   *
   * @return array
   *   Array of appointment entities.
   */
  public function getUpcomingAppointments(int $limit = 10): array {
    $storage = $this->entityTypeManager->getStorage('appointment');
    $now = time();

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('appointment_date', $now, '>')
      ->condition('status', 'cancelled', '<>')
      ->sort('appointment_date', 'ASC')
      ->range(0, $limit);

    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

}
