<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\dhcr_backend\Entity\Traits\DhcrLabelTrait;

/**
 * @ContentEntityType(
 *   id = "dhcr_city",
 *   label = @Translation("City"),
 *   label_collection = @Translation("Cities"),
 *   handlers = {
 *     "list_builder" = "Drupal\dhcr_backend\ListBuilder\DhcrCityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dhcr_backend\Form\DhcrCityForm",
 *       "edit" = "Drupal\dhcr_backend\Form\DhcrCityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "access" = "Drupal\dhcr_backend\Access\DhcrGenericAccessControlHandler"
 *   },
 *   base_table = "dhcr_city",
 *   admin_permission = "administer dhcr backend",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/content/dhcr/cities",
 *     "add-form" = "/admin/content/dhcr/cities/add",
 *     "edit-form" = "/admin/content/dhcr/cities/{dhcr_city}/edit",
 *     "delete-form" = "/admin/content/dhcr/cities/{dhcr_city}/delete"
 *   }
 * )
 */
final class City extends ContentEntityBase {
  use DhcrLabelTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['country'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Country'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'dhcr_country')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['lat'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Latitude'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 7)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 2])
      ->setDisplayConfigurable('form', TRUE);

    $fields['lng'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Longitude'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 7)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 3])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'));

    return $fields;
  }
}
