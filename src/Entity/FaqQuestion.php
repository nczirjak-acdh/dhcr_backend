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
 *   id = "dhcr_faq_question",
 *   label = @Translation("FAQ question"),
 *   label_collection = @Translation("FAQ questions"),
 *   handlers = {
 *     "list_builder" = "Drupal\dhcr_backend\ListBuilder\DhcrFaqQuestionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dhcr_backend\Form\DhcrFaqQuestionForm",
 *       "edit" = "Drupal\dhcr_backend\Form\DhcrFaqQuestionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *     "access" = "Drupal\dhcr_backend\Access\DhcrGenericAccessControlHandler"
 *   },
 *   base_table = "dhcr_faq_question",
 *   admin_permission = "administer dhcr global settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/content/dhcr/faq-questions",
 *     "add-form" = "/admin/content/dhcr/faq-questions/add",
 *     "edit-form" = "/admin/content/dhcr/faq-questions/{dhcr_faq_question}/edit",
 *     "delete-form" = "/admin/content/dhcr/faq-questions/{dhcr_faq_question}/delete"
 *   }
 * )
 */
final class FaqQuestion extends ContentEntityBase {
  use DhcrLabelTrait;
  use EntityChangedTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Question'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['category'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Category'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'public' => 'Public',
          'contributor' => 'Contributor',
          'moderator' => 'Moderator',
        ],
      ])
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['sort_order'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Sort order'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 2])
      ->setDisplayConfigurable('form', TRUE);

    $fields['answer'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Answer'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 3])
      ->setDisplayConfigurable('form', TRUE);

    $fields['link_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Link title'))
      ->setSettings(['max_length' => 100])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 4])
      ->setDisplayConfigurable('form', TRUE);

    $fields['link_url'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Link URL'))
      ->setDisplayOptions('form', ['type' => 'link_default', 'weight' => 5])
      ->setDisplayConfigurable('form', TRUE);

    $fields['published'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 6])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }
}
