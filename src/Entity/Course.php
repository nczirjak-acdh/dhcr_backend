<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\dhcr_backend\Entity\Interface\CourseInterface;
use Drupal\dhcr_backend\Entity\Traits\DhcrLabelTrait;
use Drupal\user\EntityOwnerTrait;

/**
 * @ContentEntityType(
 *   id = "dhcr_course",
 *   label = @Translation("Course"),
 *   label_collection = @Translation("Courses"),
 *   handlers = {
 *     "list_builder" = "Drupal\dhcr_backend\ListBuilder\DhcrCourseListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dhcr_backend\Form\DhcrCourseForm",
 *       "edit" = "Drupal\dhcr_backend\Form\DhcrCourseForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "access" = "Drupal\dhcr_backend\Access\DhcrCourseAccessControlHandler"
 *   },
 *   base_table = "dhcr_course",
 *   admin_permission = "administer dhcr backend",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "owner" = "uid"
 *   },
 *   links = {
 *     "collection" = "/admin/content/dhcr/courses",
 *     "add-form" = "/admin/content/dhcr/courses/add",
 *     "edit-form" = "/admin/content/dhcr/courses/{dhcr_course}/edit",
 *     "delete-form" = "/admin/content/dhcr/courses/{dhcr_course}/delete"
 *   }
 * )
 */
final class Course extends ContentEntityBase implements CourseInterface {
  use EntityChangedTrait;
  use DhcrLabelTrait;
  use EntityOwnerTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getCurrentUserId')
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => -20])
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Course Name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', ['type' => 'string', 'weight' => 0])
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['original_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Original Course Name'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 2])
      ->setDisplayConfigurable('form', TRUE);

    $fields['original_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Original Description'))
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 3])
      ->setDisplayConfigurable('form', TRUE);

    $fields['online_course'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Online Course'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 4])
      ->setDisplayConfigurable('form', TRUE);

    $fields['course_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Education Type'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'dhcr_course_type')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 5])
      ->setDisplayConfigurable('form', TRUE);

    $fields['language'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Language'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'dhcr_language')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 6])
      ->setDisplayConfigurable('form', TRUE);

    $fields['ects'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('ECTS'))
      ->setSetting('precision', 6)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 7])
      ->setDisplayConfigurable('form', TRUE);

    $fields['course_url'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Course URL'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'link_default', 'weight' => 8])
      ->setDisplayConfigurable('form', TRUE);

    $fields['contact_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Lecturer Name'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 9])
      ->setDisplayConfigurable('form', TRUE);

    $fields['contact_mail'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Lecturer E-Mail'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 10])
      ->setDisplayConfigurable('form', TRUE);

    $fields['access_requirements'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Entry Requirements'))
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 11])
      ->setDisplayConfigurable('form', TRUE);

    $fields['start_dates'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Start Date(s)'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => 12])
      ->setDisplayConfigurable('form', TRUE);

    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start date (legacy)'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', ['type' => 'datetime_default', 'weight' => 30])
      ->setDisplayConfigurable('form', TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End date (legacy)'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', ['type' => 'datetime_default', 'weight' => 31])
      ->setDisplayConfigurable('form', TRUE);

    $fields['recurring'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Recurring'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 13])
      ->setDisplayConfigurable('form', TRUE);

    $fields['duration'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duration'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 14])
      ->setDisplayConfigurable('form', TRUE);

    $fields['course_duration_unit'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Duration type'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'dhcr_duration_unit')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 15])
      ->setDisplayConfigurable('form', TRUE);

    $fields['institution'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Institution'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'dhcr_institution')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 16])
      ->setDisplayConfigurable('form', TRUE);

    $fields['country'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Country'))
      ->setSetting('target_type', 'dhcr_country')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 17])
      ->setDisplayConfigurable('form', TRUE);

    $fields['city'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('City'))
      ->setSetting('target_type', 'dhcr_city')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 18])
      ->setDisplayConfigurable('form', TRUE);

    $fields['department'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Department'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 19])
      ->setDisplayConfigurable('form', TRUE);

    $fields['lon'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Longitude'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 7)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 20])
      ->setDisplayConfigurable('form', TRUE);

    $fields['lat'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Latitude'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 7)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 21])
      ->setDisplayConfigurable('form', TRUE);

    $fields['disciplines'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Disciplines'))
      ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => 22])
      ->setDisplayConfigurable('form', TRUE);

    $fields['tadirah_techniques'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('TaDiRAH Techniques'))
      ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => 23])
      ->setDisplayConfigurable('form', TRUE);

    $fields['tadirah_objects'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('TaDiRAH Objects'))
      ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => 24])
      ->setDisplayConfigurable('form', TRUE);

    $fields['active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Show course in the registry'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 25])
      ->setDisplayConfigurable('form', TRUE);

    $fields['approved'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Approved'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 26])
      ->setDisplayConfigurable('form', TRUE);

    $fields['archived'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Archived'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 27])
      ->setDisplayConfigurable('form', TRUE);

    $fields['archived_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Archived at'))
      ->setDisplayOptions('form', ['type' => 'datetime_timestamp', 'weight' => 28])
      ->setDisplayConfigurable('form', TRUE);

    $fields['last_reminder_sent'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last reminder sent'))
      ->setDisplayOptions('form', ['type' => 'datetime_timestamp', 'weight' => 29])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'));
    $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Created'));

    return $fields;
  }

  public static function getCurrentUserId(): array {
    return [\Drupal::currentUser()->id()];
  }

}
