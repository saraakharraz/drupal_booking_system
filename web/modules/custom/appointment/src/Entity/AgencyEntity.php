<?php

namespace Drupal\appointment\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Agency entity.
 *
 * @ContentEntityType(
 *   id = "agency",
 *   label = @Translation("Agency"),
 *   label_collection = @Translation("Agencies"),
 *   label_singular = @Translation("agency"),
 *   label_plural = @Translation("agencies"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\appointment\Entity\AgencyListBuilder",
 *     "views_data" = "Drupal\appointment\Entity\AgencyViewsData",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\appointment\Form\AgencyForm",
 *       "edit" = "Drupal\appointment\Form\AgencyForm",
 *       "delete" = "Drupal\appointment\Form\AgencyDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agency",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.agency.collection",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/agencies/{agency}",
 *     "add-form" = "/admin/agencies/add",
 *     "edit-form" = "/admin/agencies/{agency}/edit",
 *     "delete-form" = "/admin/agencies/{agency}/delete",
 *     "collection" = "/admin/agencies",
 *   },
 * )
 */
class AgencyEntity extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // ID field
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The agency ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    // Agency name
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agency Name'))
      ->setDescription(t('The official name of the agency.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Address
    $fields['address'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Address'))
      ->setDescription(t('The agency address.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 1,
        'settings' => [
          'rows' => 3,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Contact phone
    $fields['contact_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Contact Phone'))
      ->setDescription(t('Agency phone number.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Contact email
    $fields['contact_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Contact Email'))
      ->setDescription(t('Agency email address.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'email_mailto',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Operating hours
//    $fields['operating_hours'] = BaseFieldDefinition::create('string_long')
//      ->setLabel(t('Operating Hours'))
//      ->setDescription(t('Agency operating hours.'))
//      ->setRequired(TRUE)
//      ->setDisplayOptions('view', [
//        'label' => 'above',
//        'type' => 'text_default',
//        'weight' => 4,
//      ])
//      ->setDisplayOptions('form', [
//        'type' => 'text_textarea',
//        'weight' => 4,
//        'settings' => [
//          'rows' => 3,
//        ],
//      ])
//      ->setDisplayConfigurable('form', TRUE)
//      ->setDisplayConfigurable('view', TRUE);

    $days = [
      'monday' => t('Monday'),
      'tuesday' => t('Tuesday'),
      'wednesday' => t('Wednesday'),
      'thursday' => t('Thursday'),
      'friday' => t('Friday'),
      'saturday' => t('Saturday'),
      'sunday' => t('Sunday'),
    ];

    $weight = 10;

    foreach ($days as $day_key => $day_label) {
      // Opening time
      $fields["{$day_key}_open"] = BaseFieldDefinition::create('string')
        ->setLabel($day_label . t(' - Opening Time'))
        ->setDescription(t('Format: HH:MM (e.g., 08:30)'))
        ->setRequired(FALSE)
        ->setSetting('max_length', 5)
        ->setDisplayOptions('view', [
          'label' => 'inline',
          'type' => 'string',
          'weight' => $weight,
        ])
        ->setDisplayOptions('form', [
          'type' => 'string_textfield',
          'weight' => $weight,
          'settings' => [
            'size' => 10,
            'placeholder' => '08:30',
          ],
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      // Closing time
      $fields["{$day_key}_close"] = BaseFieldDefinition::create('string')
        ->setLabel($day_label . t(' - Closing Time'))
        ->setDescription(t('Format: HH:MM (e.g., 17:00)'))
        ->setRequired(FALSE)
        ->setSetting('max_length', 5)
        ->setDisplayOptions('view', [
          'label' => 'inline',
          'type' => 'string',
          'weight' => $weight + 1,
        ])
        ->setDisplayOptions('form', [
          'type' => 'string_textfield',
          'weight' => $weight + 1,
          'settings' => [
            'size' => 10,
            'placeholder' => '17:00',
          ],
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $weight += 2;
    }

    // Created timestamp
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the agency was created.'));

    // Changed timestamp
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the agency was last modified.'));

    return $fields;
  }

  /**
   * Get agency name.
   *
   * @return string|null
   *   The agency name.
   */
  public function getName(): ?string {
    return $this->get('name')->value;
  }

  /**
   * Set agency name.
   *
   * @param string $name
   *   The name.
   *
   * @return $this
   */
  public function setName(string $name): static {
    $this->set('name', $name);
    return $this;
  }
  /**
   * Get opening time for a day.
   */
  public function getOpeningTime(string $day): ?string {
    return $this->get("{$day}_open")->value;
  }

  /**
   * Set opening time for a day.
   */
  public function setOpeningTime(string $day, ?string $time): static {
    $this->set("{$day}_open", $time);
    return $this;
  }

  /**
   * Get closing time for a day.
   */
  public function getClosingTime(string $day): ?string {
    return $this->get("{$day}_close")->value;
  }

  /**
   * Set closing time for a day.
   */
  public function setClosingTime(string $day, ?string $time): static {
    $this->set("{$day}_close", $time);
    return $this;
  }

  /**
   * Get all operating hours as array.
   */
  public function getOperatingHours(): array {
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $hours = [];

    foreach ($days as $day) {
      $open = $this->getOpeningTime($day);
      $close = $this->getClosingTime($day);

      $hours[$day] = [
        'open' => $open,
        'close' => $close,
        'closed' => empty($open) && empty($close),
      ];
    }

    return $hours;
  }






}
