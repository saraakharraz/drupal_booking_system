<?php

namespace Drupal\entity_export_csv;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Entity export csv entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class EntityExportCsvHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);
    $entity_type_id = $entity_type->id();
    if ($enable_form_route = $this->getFormRoute($entity_type, 'enable')) {
      $collection->add("entity.{$entity_type_id}.enable", $enable_form_route);
    }

    if ($disable_form_route = $this->getFormRoute($entity_type, 'disable')) {
      $collection->add("entity.{$entity_type_id}.disable", $disable_form_route);
    }

    if ($duplicate_form_route = $this->getFormRoute($entity_type, 'duplicate')) {
      $collection->add("entity.{$entity_type_id}.duplicate", $duplicate_form_route);
    }

    return $collection;
  }

  /**
   * Gets the form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $action
   *   The action form.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getFormRoute(EntityTypeInterface $entity_type, $action) {
    $link_template = $action;
    if ($entity_type->hasLinkTemplate($link_template)) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate($link_template));
      $operation = 'default';
      if ($entity_type->getFormClass($action)) {
        $operation = $action;
      }
      $route
        ->setDefaults([
          '_entity_form' => "{$entity_type_id}.{$operation}",
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::editTitle',
        ])
        ->setRequirement('_entity_access', "{$entity_type_id}.update")
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

}
