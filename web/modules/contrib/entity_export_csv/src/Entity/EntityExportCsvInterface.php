<?php

namespace Drupal\entity_export_csv\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Entity export csv entities.
 */
interface EntityExportCsvInterface extends ConfigEntityInterface {

  /**
   * Get the entity type id.
   *
   * @return string
   *   The entity type id.
   */
  public function getTargetEntityTypeId();

  /**
   * Set the target entity type id.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return $this
   *   The config entity.
   */
  public function setTargetEntityTypeId($entity_type_id);

  /**
   * Get the bundle.
   *
   * @return string
   *   The bundle.
   */
  public function getTargetBundle();

  /**
   * Set the bundle.
   *
   * @param string $bundle
   *   The bundle.
   *
   * @return $this
   *   The config entity.
   */
  public function setTargetBundle($bundle);

  /**
   * Get the delimiter.
   *
   * @return string
   *   The delimiter.
   */
  public function getDelimiter();

  /**
   * Set the delimiter.
   *
   * @param string $delimiter
   *   The delimiter.
   *
   * @return $this
   *   The config entity.
   */
  public function setDelimiter($delimiter);

  /**
   * Get the fields configuration.
   *
   * @return array
   *   The fields configuration.
   */
  public function getFields();

  /**
   * Set the fields configuration.
   *
   * @param array $fields
   *   The fields configuration.
   *
   * @return $this
   *   The config entity.
   */
  public function setFields(array $fields);

  /**
   * Get the langcode.
   *
   * @return string
   *   The langcode.
   */
  public function getLangCode();

  /**
   * Set the langcode.
   *
   * @param string $langcode
   *   The langcode.
   *
   * @return $this
   *   The config entity.
   */
  public function setLangcode(string $langcode);

}
