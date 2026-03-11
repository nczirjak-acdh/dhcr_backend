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
 *   id = "dhcr_contributor_profile",
 *   label = @Translation("Contributor profile"),
 *   label_collection = @Translation("Contributor profiles"),
 *   handlers = {
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "access" = "Drupal\dhcr_backend\Access\DhcrGenericAccessControlHandler"
 *   },
 *   base_table = "dhcr_contributor_profile",
 *   admin_permission = "administer dhcr backend",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "delete-form" = "/admin/content/dhcr/contributor-profiles/{dhcr_contributor_profile}/delete"
 *   }
 * )
 */
final class ContributorProfile extends ContentEntityBase {
  use DhcrLabelTrait;
  use EntityChangedTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255]);

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user');

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setRequired(TRUE);

    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First name'))
      ->setSettings(['max_length' => 255]);

    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last name'))
      ->setSettings(['max_length' => 255]);

    $fields['academic_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Academic title'))
      ->setSettings(['max_length' => 255]);

    $fields['institution'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Institution'))
      ->setSetting('target_type', 'dhcr_institution');

    $fields['other_organisation'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Other organisation'));

    $fields['enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Account enabled'))
      ->setDefaultValue(TRUE);

    $fields['moderator'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Moderator'))
      ->setDefaultValue(FALSE);

    $fields['legacy_user_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Legacy user ID'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }
}
