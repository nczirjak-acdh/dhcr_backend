<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class DhcrGenericAccessControlHandler extends EntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Simple rule: only admins can manage master-data entities.
    return AccessResult::allowedIfHasPermission($account, 'administer dhcr backend');
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer dhcr backend');
  }

}
