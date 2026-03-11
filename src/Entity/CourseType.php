<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\dhcr_backend\Entity\Traits\DhcrLabelTrait;

/**
 * @ContentEntityType(
 *   id = "dhcr_course_type",
 *   label = @Translation("Course type"),
 *   label_collection = @Translation("Course types"),
 *   handlers = {
 *     "list_builder" = "Drupal\dhcr_backend\ListBuilder\DhcrGenericListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dhcr_backend\Form\DhcrContentEntityForm",
 *       "edit" = "Drupal\dhcr_backend\Form\DhcrContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "access" = "Drupal\dhcr_backend\Access\DhcrGenericAccessControlHandler"
 *   },
 *   base_table = "dhcr_course_type",
 *   admin_permission = "administer dhcr backend",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/content/dhcr/course-types",
 *     "add-form" = "/admin/content/dhcr/course-types/add",
 *     "edit-form" = "/admin/content/dhcr/course-types/{dhcr_course_type}/edit",
 *     "delete-form" = "/admin/content/dhcr/course-types/{dhcr_course_type}/delete"
 *   }
 * )
 */
final class CourseType extends ContentEntityBase {
  use DhcrLabelTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'));
    return $fields;
  }
}
