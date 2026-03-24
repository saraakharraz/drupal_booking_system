<?php

namespace Drupal\appointment\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for Appointment entity.
 *
 * Defines methods for Appointment entities.
 * All entities should implement this interface.
 */
interface AppointmentEntityInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Get appointment title.
   *
   * @return string|null
   *   The appointment title.
   */
  public function getTitle(): ?string;

  /**
   * Set appointment title.
   *
   * @param string $title
   *   The title.
   *
   * @return $this
   */
  public function setTitle(string $title): static;

  /**
   * Get appointment date/time.
   *
   * @return string|null
   *   The appointment date/time.
   */
  public function getAppointmentDate(): ?string;

  /**
   * Set appointment date/time.
   *
   * @param string $date
   *   The date/time string.
   *
   * @return $this
   */
  public function setAppointmentDate(string $date): static;

  /**
   * Get appointment status.
   *
   * @return string|null
   *   Status: pending, confirmed, or cancelled.
   */
  public function getStatus(): ?string;

  /**
   * Set appointment status.
   *
   * @param string $status
   *   Status value.
   *
   * @return $this
   */
  public function setStatus(string $status): static;

  /**
   * Get customer name.
   *
   * @return string|null
   *   Customer name.
   */
  public function getCustomerName(): ?string;

  /**
   * Set customer name.
   *
   * @param string $name
   *   Customer name.
   *
   * @return $this
   */
  public function setCustomerName(string $name): static;

  /**
   * Get customer email.
   *
   * @return string|null
   *   Customer email.
   */
  public function getCustomerEmail(): ?string;

  /**
   * Set customer email.
   *
   * @param string $email
   *   Customer email.
   *
   * @return $this
   */
  public function setCustomerEmail(string $email): static;

  /**
   * Get customer phone.
   *
   * @return string|null
   *   Customer phone.
   */
  public function getCustomerPhone(): ?string;

  /**
   * Set customer phone.
   *
   * @param string $phone
   *   Customer phone.
   *
   * @return $this
   */
  public function setCustomerPhone(string $phone): static;

  /**
   * Get agency ID.
   *
   * @return int|null
   *   Agency ID.
   */
  public function getAgencyId(): ?int;

  /**
   * Set agency.
   *
   * @param int $agency_id
   *   Agency ID.
   *
   * @return $this
   */
  public function setAgencyId(int $agency_id): static;

  /**
   * Get adviser (user) ID.
   *
   * @return int|null
   *   User ID.
   */
  public function getAdviserId(): ?int;

  /**
   * Set adviser (user).
   *
   * @param int $adviser_id
   *   User ID.
   *
   * @return $this
   */
  public function setAdviserId(int $adviser_id): static;

  /**
   * Get appointment type (taxonomy term).
   *
   * @return int|null
   *   Taxonomy term ID.
   */
  public function getAppointmentTypeId(): ?int;

  /**
   * Set appointment type.
   *
   * @param int $type_id
   *   Taxonomy term ID.
   *
   * @return $this
   */
  public function setAppointmentTypeId(int $type_id): static;

  /**
   * Get appointment notes.
   *
   * @return string|null
   *   Notes text.
   */
  public function getNotes(): ?string;

  /**
   * Set appointment notes.
   *
   * @param string $notes
   *   Notes text.
   *
   * @return $this
   */
  public function setNotes(string $notes): static;

  /**
   * Get creation timestamp.
   *
   * @return int
   *   Timestamp.
   */
  public function getCreatedTime(): int;

  /**
   * Set creation timestamp.
   *
   * @param int $timestamp
   *   Timestamp.
   *
   * @return $this
   */
  public function setCreatedTime(int $timestamp): static;

}
