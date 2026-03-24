<?php

namespace Drupal\appointment\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
/**
 * Defines the list builder for Agency entities.
 */
class AgencyListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Agency Name');
    $header['contact_phone'] = $this->t('Phone');
    $header['contact_email'] = $this->t('Email');
    $header['operations'] = $this->t('Operations');

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\appointment\Entity\AgencyEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->getName(),
      'entity.agency.canonical',
      ['agency' => $entity->id()]
    );
    $row['contact_phone'] = $entity->get('contact_phone')->value;
    $row['contact_email'] = $entity->get('contact_email')->value;

    $edit_url = Url::fromRoute('entity.agency.edit_form', ['agency' => $entity->id()]);
    $delete_url = Url::fromRoute('entity.agency.delete_form', ['agency' => $entity->id()]);

    $row['operations'] = [
      'data' => [
        '#type' => 'dropbutton',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => $edit_url,
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => $delete_url,
          ],
        ],
      ],
    ];

    return $row;
  }

}
