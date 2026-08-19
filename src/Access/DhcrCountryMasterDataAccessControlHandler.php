<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Country-scoped access for cities and institutions.
 */
final class DhcrCountryMasterDataAccessControlHandler extends DhcrGenericAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer_dhcr_global_settings')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    return AccessResult::allowedIf(
      $account->hasPermission('manage_dhcr_country_master_data')
      && DhcrCountryScope::matchesEntity($account, $entity)
    )->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage_dhcr_country_master_data',
      'administer_dhcr_global_settings',
    ], 'OR');
  }

}
