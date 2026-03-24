<?php

namespace Drupal\appointment;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

class AppointmentAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {

      case 'view':
        return AccessResult::allowed();

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit any appointment');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'cancel any appointment');
    }

    return AccessResult::neutral();
  }

}
