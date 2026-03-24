<?php

namespace Drupal\entity_export_csv\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Entity export csv entity.
 *
 * @ConfigEntityType(
 *   id = "entity_export_csv",
 *   label = @Translation("Entity export csv configuration"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\entity_export_csv\EntityExportCsvListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_export_csv\Form\EntityExportCsvForm",
 *       "edit" = "Drupal\entity_export_csv\Form\EntityExportCsvForm",
 *       "delete" = "Drupal\entity_export_csv\Form\EntityExportCsvDeleteForm",
 *       "enable" = "Drupal\entity_export_csv\Form\EntityExportCsvEnableForm",
 *       "disable" = "Drupal\entity_export_csv\Form\EntityExportCsvDisableForm",
 *       "duplicate" = "Drupal\entity_export_csv\Form\EntityExportCsvDuplicateForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity_export_csv\EntityExportCsvHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "entity_export_csv",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   lookup_keys = {
 *     "entity_type_id",
 *     "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/content/entity-export-csv/configurations/{entity_export_csv}",
 *     "add-form" = "/admin/config/content/entity-export-csv/configurations/add",
 *     "edit-form" = "/admin/config/content/entity-export-csv/configurations/{entity_export_csv}/edit",
 *     "delete-form" = "/admin/config/content/entity-export-csv/configurations/{entity_export_csv}/delete",
 *     "enable" = "/admin/config/content/entity-export-csv/configurations/{entity_export_csv}/enable",
 *     "disable" = "/admin/config/content/entity-export-csv/configurations/{entity_export_csv}/disable",
 *     "duplicate" = "/admin/config/content/entity-export-csv/configurations/{entity_export_csv}/duplicate",
 *     "collection" = "/admin/config/content/entity-export-csv/configurations"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "status",
 *     "langcode",
 *     "entity_type_id",
 *     "bundle",
 *     "fields",
 *     "delimiter",
 *   }
 * )
 */
class EntityExportCsv extends ConfigEntityBase implements EntityExportCsvInterface {

  /**
   * The Entity export csv ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Entity export csv label.
   *
   * @var string
   */
  protected $label;

  /**
   * The entity type id to export.
   *
   * @var string
   */
  protected $entity_type_id;

  /**
   * The bundle to export.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The csv delimiter.
   *
   * @var string
   */
  protected $delimiter;

  /**
   * The langcode.
   *
   * @var string
   */
  protected $langcode;

  /**
   * The fields configurations.
   *
   * @var array
   */
  protected $fields;

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    return $this->entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetEntityTypeId($entity_type_id) {
    $this->entity_type_id = $entity_type_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetBundle($bundle) {
    $this->bundle = $bundle;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDelimiter() {
    return $this->delimiter;
  }

  /**
   * {@inheritdoc}
   */
  public function setDelimiter($delimiter) {
    $this->delimiter = $delimiter;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $fields) {
    $this->fields = $fields;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangCode() {
    return $this->langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function setLangcode(string $langcode) {
    $this->langcode = $langcode;
    return $this;
  }

}
