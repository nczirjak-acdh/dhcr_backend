<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\dhcr_backend\Entity\Traits\DhcrLabelTrait;

/**
 * @ContentEntityType(
 *   id = "dhcr_country",
 *   label = @Translation("Country"),
 *   label_collection = @Translation("Countries"),
 *   handlers = {
 *     "list_builder" = "Drupal\dhcr_backend\ListBuilder\DhcrCountryListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dhcr_backend\Form\DhcrCountryForm",
 *       "edit" = "Drupal\dhcr_backend\Form\DhcrCountryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "access" = "Drupal\dhcr_backend\Access\DhcrGenericAccessControlHandler"
 *   },
 *   base_table = "dhcr_country",
 *   admin_permission = "administer dhcr backend",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/content/dhcr/countries",
 *     "add-form" = "/admin/content/dhcr/countries/add",
 *     "edit-form" = "/admin/content/dhcr/countries/{dhcr_country}/edit",
 *     "delete-form" = "/admin/content/dhcr/countries/{dhcr_country}/delete"
 *   }
 * )
 */
final class Country extends ContentEntityBase {
  use DhcrLabelTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', ['type' => 'string', 'weight' => 0])
      ->setDisplayConfigurable('view', TRUE);

    $fields['iso2'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ISO 3166-1 alpha-2'))
      ->setSettings(['max_length' => 2])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }
}
