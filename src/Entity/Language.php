<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\dhcr_backend\Entity\Traits\DhcrLabelTrait;

/**
 * @ContentEntityType(
 *   id = "dhcr_language",
 *   label = @Translation("Language"),
 *   label_collection = @Translation("Languages"),
 *   handlers = {
 *     "list_builder" = "Drupal\dhcr_backend\ListBuilder\DhcrLanguageListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dhcr_backend\Form\DhcrLanguageForm",
 *       "edit" = "Drupal\dhcr_backend\Form\DhcrLanguageForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "access" = "Drupal\dhcr_backend\Access\DhcrGenericAccessControlHandler"
 *   },
 *   base_table = "dhcr_language",
 *   admin_permission = "administer dhcr global settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/content/dhcr/languages",
 *     "add-form" = "/admin/content/dhcr/languages/add",
 *     "edit-form" = "/admin/content/dhcr/languages/{dhcr_language}/edit",
 *     "delete-form" = "/admin/content/dhcr/languages/{dhcr_language}/delete"
 *   }
 * )
 */
final class Language extends ContentEntityBase {
  use DhcrLabelTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['iso'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ISO code'))
      ->setSettings(['max_length' => 10])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'));

    return $fields;
  }
}
