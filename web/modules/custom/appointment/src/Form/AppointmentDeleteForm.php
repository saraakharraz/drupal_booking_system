<?php

namespace Drupal\appointment\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a deletion confirmation form for Appointment entities.
 */
class AppointmentDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete appointment %title?', [
      '%title' => $this->entity->getTitle(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('collection');
  }

}
