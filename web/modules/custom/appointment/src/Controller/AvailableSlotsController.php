<?php

namespace Drupal\appointment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller for available appointment slots.
 */
class AvailableSlotsController extends ControllerBase {

  /**
   * Get available slots based on agency and adviser.
   */
  public function getSlots(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $agency_id = $data['agency_id'] ?? NULL;
    $adviser_id = $data['adviser_id'] ?? NULL;
    $start_date = $data['start'] ?? NULL;
    $end_date = $data['end'] ?? NULL;

    if (!$agency_id || !$adviser_id || !$start_date || !$end_date) {
      return new JsonResponse(['error' => 'Missing parameters'], 400);
    }

    $slots = [];

    // Load agency and adviser
    $agency = $this->entityTypeManager()->getStorage('agency')->load($agency_id);
    $adviser = $this->entityTypeManager()->getStorage('user')->load($adviser_id);

    if (!$agency || !$adviser) {
      return new JsonResponse(['error' => 'Agency or adviser not found'], 404);
    }

    // Generate available slots
    $current = new \DateTime($start_date);
    $end = new \DateTime($end_date);
    $end->modify('+1 day'); // Include end date

    while ($current < $end) {
      $day_of_week = $current->format('N'); // 1=Monday, 7=Sunday
      $date_str = $current->format('Y-m-d');

      // Check if agency is open this day
      $agency_hours = $this->getAgencyHours($agency, $day_of_week);
      $adviser_hours = $this->getAdviserHours($adviser);

      if ($agency_hours && $adviser_hours) {
        // Get overlapping hours
        $start_time = max($agency_hours['start'], $adviser_hours['start']);
        $end_time = min($agency_hours['end'], $adviser_hours['end']);

        if ($start_time < $end_time) {
          // Generate 30-minute slots
          $slot_start = new \DateTime($date_str . ' ' . $start_time);
          $slot_end = new \DateTime($date_str . ' ' . $end_time);

          while ($slot_start < $slot_end) {
            $slot_end_time = clone $slot_start;
            $slot_end_time->modify('+30 minutes');

            // Check if slot is available (no existing appointments)
            if ($this->isSlotAvailable($adviser_id, $slot_start, $slot_end_time)) {
              $slots[] = [
                'id' => $slot_start->getTimestamp(),
                'title' => 'Available',
                'start' => $slot_start->toAtom(),
                'end' => $slot_end_time->toAtom(),
                'backgroundColor' => '#4CAF50',
                'borderColor' => '#2E7D32',
                'extendedProps' => [
                  'available' => TRUE,
                ],
              ];
            }

            $slot_start->modify('+30 minutes');
          }
        }
      }

      $current->modify('+1 day');
    }

    // Add existing appointments as blocked slots
    $blocked = $this->getBlockedSlots($adviser_id, $start_date, $end_date);
    $slots = array_merge($slots, $blocked);

    return new JsonResponse($slots);
  }

  /**
   * Get agency opening hours for a specific day.
   * Days: 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday, 7=Sunday
   */
  protected function getAgencyHours($agency, $day_of_week) {
    $day_names = ['', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $day_key = $day_names[$day_of_week] ?? NULL;

    if (!$day_key) {
      return NULL;
    }

    // Agency fields: field_monday_open, field_monday_close, etc.
    $open_field = 'field_' . $day_key . '_open';
    $close_field = 'field_' . $day_key . '_close';

    $open_time = $agency->get($open_field)->value;
    $close_time = $agency->get($close_field)->value;

    if (!$open_time || !$close_time) {
      return NULL;
    }

    return [
      'start' => $open_time,
      'end' => $close_time,
    ];
  }

  /**
   * Get adviser working hours.
   * Format: "08:30-17:00"
   */
  protected function getAdviserHours($adviser) {
    $hours = $adviser->get('field_adviser_working_hours')->value;

    if (!$hours) {
      return NULL;
    }

    // Format: "08:30-17:00"
    $parts = explode('-', $hours);
    if (count($parts) !== 2) {
      return NULL;
    }

    return [
      'start' => trim($parts[0]),
      'end' => trim($parts[1]),
    ];
  }

  /**
   * Check if slot is available (no existing appointments).
   */
  protected function isSlotAvailable($adviser_id, \DateTime $start, \DateTime $end) {
    $appointments = $this->entityTypeManager()
      ->getStorage('appointment')
      ->loadByProperties([
        'adviser' => $adviser_id,
        'status' => ['pending', 'confirmed'],
      ]);

    foreach ($appointments as $appointment) {
      $appt_date_str = $appointment->get('appointment_date')->value;
      $appt_date = new \DateTime($appt_date_str);

      // Check if there's a conflict
      if ($appt_date >= $start && $appt_date < $end) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Get blocked slots (existing appointments).
   */
  protected function getBlockedSlots($adviser_id, $start_date, $end_date) {
    $appointments = $this->entityTypeManager()
      ->getStorage('appointment')
      ->loadByProperties([
        'adviser' => $adviser_id,
        'status' => ['pending', 'confirmed'],
      ]);

    $blocked = [];

    foreach ($appointments as $appointment) {
      $appt_date = $appointment->get('appointment_date')->value;
      $appt_time = new \DateTime($appt_date);

      if ($appt_time->format('Y-m-d') >= $start_date && $appt_time->format('Y-m-d') <= $end_date) {
        $end_time = clone $appt_time;
        $end_time->modify('+30 minutes');

        $blocked[] = [
          'id' => 'blocked_' . $appointment->id(),
          'title' => 'Unavailable',
          'start' => $appt_time->toAtom(),
          'end' => $end_time->toAtom(),
          'backgroundColor' => '#F44336',
          'borderColor' => '#C62828',
          'extendedProps' => [
            'available' => FALSE,
          ],
        ];
      }
    }

    return $blocked;
  }

}
