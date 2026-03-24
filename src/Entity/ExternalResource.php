<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\dhcr_backend\Entity\Traits\DhcrLabelTrait;

/**
 * @ContentEntityType(
 *   id = "dhcr_external_resource",
 *   label = @Translation("External resource"),
 *   label_collection = @Translation("External resources"),
 *   handlers = {
 *     "list_builder" = "Drupal\dhcr_backend\ListBuilder\DhcrExternalResourceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dhcr_backend\Form\DhcrExternalResourceForm",
 *       "edit" = "Drupal\dhcr_backend\Form\DhcrExternalResourceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "access" = "Drupal\dhcr_backend\Access\DhcrGenericAccessControlHandler"
 *   },
 *   base_table = "dhcr_external_resource",
 *   admin_permission = "administer dhcr global settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/content/dhcr/external-resources",
 *     "add-form" = "/admin/content/dhcr/external-resources/add",
 *     "edit-form" = "/admin/content/dhcr/external-resources/{dhcr_external_resource}/edit",
 *     "delete-form" = "/admin/content/dhcr/external-resources/{dhcr_external_resource}/delete"
 *   }
 * )
 */
final class ExternalResource extends ContentEntityBase {
  use DhcrLabelTrait;
  use EntityChangedTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['course'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Course'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'dhcr_course')
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['resource_url'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Url'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'link_default', 'weight' => 2])
      ->setDisplayConfigurable('form', TRUE);

    $fields['resource_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setSettings(['max_length' => 100])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 3])
      ->setDisplayConfigurable('form', TRUE);

    $fields['affiliation'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Affiliation'))
      ->setSettings(['max_length' => 100])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 4])
      ->setDisplayConfigurable('form', TRUE);

    $fields['visible'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Visible'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 5])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }
}
