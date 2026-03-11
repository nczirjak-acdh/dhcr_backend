<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\dhcr_backend\Entity\Traits\DhcrLabelTrait;

/**
 * @ContentEntityType(
 *   id = "dhcr_institution",
 *   label = @Translation("Institution"),
 *   label_collection = @Translation("Institutions"),
 *   handlers = {
 *     "list_builder" = "Drupal\dhcr_backend\ListBuilder\DhcrInstitutionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dhcr_backend\Form\DhcrInstitutionForm",
 *       "edit" = "Drupal\dhcr_backend\Form\DhcrInstitutionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "access" = "Drupal\dhcr_backend\Access\DhcrGenericAccessControlHandler"
 *   },
 *   base_table = "dhcr_institution",
 *   admin_permission = "administer dhcr backend",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/content/dhcr/institutions",
 *     "add-form" = "/admin/content/dhcr/institutions/add",
 *     "edit-form" = "/admin/content/dhcr/institutions/{dhcr_institution}/edit",
 *     "delete-form" = "/admin/content/dhcr/institutions/{dhcr_institution}/delete"
 *   }
 * )
 */
final class Institution extends ContentEntityBase {
  use DhcrLabelTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['city'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('City'))
      ->setSetting('target_type', 'dhcr_city')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['country'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Country'))
      ->setSetting('target_type', 'dhcr_country')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 2])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 3])
      ->setDisplayConfigurable('form', TRUE);

    $fields['website'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Url'))
      ->setDisplayOptions('form', ['type' => 'link_default', 'weight' => 4])
      ->setDisplayConfigurable('form', TRUE);

    $fields['lon'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Longitude'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 7)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 5])
      ->setDisplayConfigurable('form', TRUE);

    $fields['lat'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Latitude'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 7)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 6])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'));

    return $fields;
  }
}
