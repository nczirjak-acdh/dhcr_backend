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
 *   id = "dhcr_user_invitation",
 *   label = @Translation("User invitation"),
 *   label_collection = @Translation("User invitations"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\dhcr_backend\Form\DhcrInviteUserForm",
 *       "edit" = "Drupal\dhcr_backend\Form\DhcrInviteUserForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "access" = "Drupal\dhcr_backend\Access\DhcrGenericAccessControlHandler"
 *   },
 *   base_table = "dhcr_user_invitation",
 *   admin_permission = "administer dhcr backend",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/content/dhcr/contributor-network/pending-invitations",
 *     "add-form" = "/admin/content/dhcr/contributor-network/invite",
 *     "edit-form" = "/admin/content/dhcr/contributor-network/invitations/{dhcr_user_invitation}/edit",
 *     "delete-form" = "/admin/content/dhcr/contributor-network/invitations/{dhcr_user_invitation}/delete"
 *   }
 * )
 */
final class UserInvitation extends ContentEntityBase {
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
      ->setSetting('target_type', 'user');

    $fields['institution'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Institution'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'dhcr_institution');

    $fields['academic_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Academic title'))
      ->setSettings(['max_length' => 255]);

    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255]);

    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255]);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Institutional email address'))
      ->setRequired(TRUE);

    $fields['localization'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Choose localization'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'dhcr_language');

    $fields['account_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Account enabled'))
      ->setDefaultValue(TRUE);

    $fields['valid_until'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Invitation valid until'));

    $fields['legacy_user_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Legacy user ID'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }
}
