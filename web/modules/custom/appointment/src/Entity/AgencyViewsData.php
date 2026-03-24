<?php

namespace Drupal\appointment\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Agency entities.
 */
class AgencyViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['agency']['name']['field']['id'] = 'string';
    $data['agency']['name']['filter']['id'] = 'string';
    $data['agency']['name']['sort']['id'] = 'standard';

    return $data;
  }

}
