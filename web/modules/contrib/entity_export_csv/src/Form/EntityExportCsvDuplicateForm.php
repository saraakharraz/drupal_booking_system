<?php

namespace Drupal\entity_export_csv\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityExportCsvDuplicateForm.
 *
 * @package Drupal\entity_export_csv\Form
 */
class EntityExportCsvDuplicateForm extends EntityExportCsvForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\offcanvas_edit\Entity\OffcanvasEditInterface $entity */
    $entity = $this->entity->createDuplicate();
    $entity->set('label', $this->t('Duplicate of @label', ['@label' => $this->entity->label()]));
    $this->entity = $entity;
    return parent::form($form, $form_state);
  }

}
