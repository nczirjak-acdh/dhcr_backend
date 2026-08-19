<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

final class DhcrCourseAccessControlHandler extends DhcrGenericAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer_dhcr_global_settings')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($operation === 'view') {
      $is_public = (int) ($entity->get('active')->value ?? 0) === 1
        && (int) ($entity->get('approved')->value ?? 0) === 1
        && (int) ($entity->get('archived')->value ?? 0) === 0;
      $is_owner = $account->isAuthenticated()
        && $account->hasPermission('view_own_dhcr_courses')
        && (int) ($entity->get('uid')->target_id ?? 0) === (int) $account->id();
      $is_moderator = $account->hasPermission('moderate_dhcr_country_courses')
        && DhcrCountryScope::matchesEntity($account, $entity);
      return AccessResult::allowedIf($is_public || $is_owner || $is_moderator)
        ->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
    }

    if ($operation === 'update') {
      $is_owner = $account->hasPermission('edit_own_dhcr_courses')
        && (int) ($entity->get('uid')->target_id ?? 0) === (int) $account->id();
      $is_moderator = $account->hasPermission('moderate_dhcr_country_courses')
        && DhcrCountryScope::matchesEntity($account, $entity);
      return AccessResult::allowedIf($is_owner || $is_moderator)
        ->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
    }

    return AccessResult::forbidden()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'create_dhcr_courses');
  }

}
