<?php

namespace Drupal\appointment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for exporting all agencies as JSON.
 */
class AgencyExportController extends ControllerBase {

  /**
   * Entity type manager.
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Export all agencies with operating hours as JSON.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response with JSON file download.
   */
  public function exportAgenciesJson(): Response {
    try {
      // Load all agencies
      $agency_storage = $this->entityTypeManager->getStorage('agency');
      $agencies = $agency_storage->loadMultiple();

      // Build the export data
      $data = [];

      foreach ($agencies as $agency) {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $operating_hours = [];

        foreach ($days as $day) {
          $operating_hours[$day] = [
            'open' => $agency->get("{$day}_open")->value,
            'close' => $agency->get("{$day}_close")->value,
            'closed' => empty($agency->get("{$day}_open")->value) && empty($agency->get("{$day}_close")->value),
          ];
        }

        $data[] = [
          'id' => $agency->id(),
          'name' => $agency->getName(),
          'address' => $agency->get('address')->value,
          'contact_phone' => $agency->get('contact_phone')->value,
          'contact_email' => $agency->get('contact_email')->value,
          'operating_hours' => $operating_hours,
          'created' => $agency->get('created')->value,
          'changed' => $agency->get('changed')->value,
        ];
      }

      // Convert to JSON
      $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

      // Create response with JSON file download
      $response = new Response($json);
      $response->headers->set('Content-Type', 'application/json');
      $response->headers->set(
        'Content-Disposition',
        'attachment; filename="agencies_' . date('Y-m-d_H-i-s') . '.json"'
      );

      return $response;

    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error exporting agencies: @error', ['@error' => $e->getMessage()]));
      return $this->redirect('entity.agency.collection');
    }
  }

}
