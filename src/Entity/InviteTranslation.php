<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "dhcr_invite_translation",
 *   label = @Translation("Translation"),
 *   label_collection = @Translation("Translations"),
 *   handlers = {
 *     "list_builder" = "Drupal\dhcr_backend\ListBuilder\DhcrInviteTranslationListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dhcr_backend\Form\DhcrInviteTranslationForm",
 *       "edit" = "Drupal\dhcr_backend\Form\DhcrInviteTranslationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "access" = "Drupal\dhcr_backend\Access\DhcrGenericAccessControlHandler"
 *   },
 *   base_table = "dhcr_invite_translation",
 *   admin_permission = "administer dhcr global settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "subject",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/content/dhcr/translations",
 *     "add-form" = "/admin/content/dhcr/translations/add",
 *     "edit-form" = "/admin/content/dhcr/translations/{dhcr_invite_translation}/edit",
 *     "delete-form" = "/admin/content/dhcr/translations/{dhcr_invite_translation}/delete"
 *   }
 * )
 */
final class InviteTranslation extends ContentEntityBase {
  use EntityChangedTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['sort_order'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Sort order'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['language'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Language'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'dhcr_language')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 150])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 2])
      ->setDisplayConfigurable('form', TRUE);

    $fields['message_body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Message'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 3])
      ->setDisplayConfigurable('form', TRUE);

    $fields['published'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publish'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 4])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
