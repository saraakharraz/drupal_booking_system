<?php

namespace Drupal\appointment\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a deletion confirmation form for Agency entities.
 */
class AgencyDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete agency %name?', [
      '%name' => $this->entity->getName(),
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
