<?php

namespace Drupal\appointment\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Appointment entity.
 *
 * @ContentEntityType(
 *   id = "appointment",
 *   label = @Translation("Appointment"),
 *   label_collection = @Translation("Appointments"),
 *   label_singular = @Translation("appointment"),
 *   label_plural = @Translation("appointments"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\appointment\Entity\AppointmentListBuilder",
 *     "views_data" = "Drupal\appointment\Entity\AppointmentViewsData",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\appointment\Form\AppointmentSubmitForm",
 *       "edit" = "Drupal\appointment\Form\AppointmentSubmitForm",
 *       "delete" = "Drupal\appointment\Form\AppointmentDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "appointment",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.appointment.collection",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/appointments/{appointment}",
 *     "add-form" = "/appointments/add",
 *     "edit-form" = "/appointments/{appointment}/edit",
 *     "delete-form" = "/appointments/{appointment}/delete",
 *     "collection" = "/admin/appointments",
 *   },
 * )
 */
class AppointmentEntity extends ContentEntityBase implements AppointmentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // ID field (auto-generated)
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The appointment ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    // Title field
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The appointment title.'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
        'settings' => [
          'placeholder' => 'Leave empty for auto-generated title',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Appointment date/time
    $fields['appointment_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date and Time'))
      ->setDescription(t('The appointment date and time.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Status field
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('The appointment status.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'pending' => t('Pending'),
        'confirmed' => t('Confirmed'),
        'cancelled' => t('Cancelled'),
      ])
      ->setDefaultValue('pending')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Agency reference
    $fields['agency'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Agency'))
      ->setDescription(t('The agency providing this appointment.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'agency')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Adviser (User) reference
    $fields['adviser'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Adviser'))
      ->setDescription(t('The adviser handling this appointment.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Appointment type (Taxonomy)
    $fields['appointment_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Appointment Type'))
      ->setDescription(t('The type of appointment (specialization).'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => ['appointment_type' => 'appointment_type'],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Customer name
    $fields['customer_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer Name'))
      ->setDescription(t('Full name of the customer.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Customer email
    $fields['customer_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Customer Email'))
      ->setDescription(t('Customer email address.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'email_mailto',
        'weight' => 7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Customer phone
    $fields['customer_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer Phone'))
      ->setDescription(t('Customer phone number.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Notes field
    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Additional notes about the appointment.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 9,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Created timestamp
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the appointment was created.'))
      ->setReadOnly(TRUE);

    // Changed timestamp
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the appointment was last modified.'))
      ->setReadOnly(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): ?string {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle(string $title): static {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAppointmentDate(): ?string {
    return $this->get('appointment_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAppointmentDate(string $date): static {
    $this->set('appointment_date', $date);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): ?string {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus(string $status): static {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomerName(): ?string {
    return $this->get('customer_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomerName(string $name): static {
    $this->set('customer_name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomerEmail(): ?string {
    return $this->get('customer_email')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomerEmail(string $email): static {
    $this->set('customer_email', $email);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomerPhone(): ?string {
    return $this->get('customer_phone')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomerPhone(string $phone): static {
    $this->set('customer_phone', $phone);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAgencyId(): ?int {
    return $this->get('agency')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setAgencyId(int $agency_id): static {
    $this->set('agency', $agency_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdviserId(): ?int {
    return $this->get('adviser')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdviserId(int $adviser_id): static {
    $this->set('adviser', $adviser_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAppointmentTypeId(): ?int {
    return $this->get('appointment_type')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setAppointmentTypeId(int $type_id): static {
    $this->set('appointment_type', $type_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotes(): ?string {
    return $this->get('notes')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNotes(string $notes): static {
    $this->set('notes', $notes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return (int) $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): static {
    $this->set('created', $timestamp);
    return $this;
  }

  public function getChangedTime()
  {
    // TODO: Implement getChangedTime() method.
  }

  public function setChangedTime($timestamp)
  {
    // TODO: Implement setChangedTime() method.
  }

  public function getChangedTimeAcrossTranslations()
  {
    // TODO: Implement getChangedTimeAcrossTranslations() method.
  }
}
