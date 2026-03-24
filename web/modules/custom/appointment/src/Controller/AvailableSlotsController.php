<?php

namespace Drupal\appointment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AvailableSlotsController extends ControllerBase {

  /**
   * Retourne uniquement les dates réservées pour agency + adviser.
   * Le JS se charge de générer les créneaux et de les colorier.
   */
  public function getSlots(Request $request): JsonResponse {
    $data       = json_decode($request->getContent(), TRUE);
    $agency_id  = $data['agency_id']  ?? NULL;
    $adviser_id = $data['adviser_id'] ?? NULL;

    if (!$agency_id || !$adviser_id) {
      return new JsonResponse(['error' => 'Paramètres manquants'], 400);
    }

    // Filtrer les appointments par agency + adviser + status != cancelled
    $query = $this->entityTypeManager()
      ->getStorage('appointment')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('agency',  $agency_id)
      ->condition('adviser', $adviser_id)
      ->condition('status',  'cancelled', '!=')
      ->execute();

    $appointments = $this->entityTypeManager()
      ->getStorage('appointment')
      ->loadMultiple($query);

    // Retourner uniquement les datetime réservées ex: ["2026-03-24T09:00", ...]
    $booked = [];
    foreach ($appointments as $appt) {
      $value = $appt->get('appointment_date')->value;
      if ($value) {
        // Format ISO sans secondes pour correspondre à ce que le JS compare
        $dt       = new \DateTime($value);
        $booked[] = $dt->format('Y-m-d\TH:i');
      }
    }

    return new JsonResponse($booked);
  }

}
