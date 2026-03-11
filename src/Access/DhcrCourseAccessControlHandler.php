<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

final class DhcrCourseAccessControlHandler extends DhcrGenericAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // Admins can do anything.
    if ($account->hasPermission('administer dhcr backend')) {
      return AccessResult::allowed();
    }

    // Logged-in users can view.
    if ($operation === 'view') {
      return AccessResult::allowedIf($account->isAuthenticated());
    }

    // Contributors can update/delete own course.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      if ($entity->hasField('uid') && (int) $entity->get('uid')->target_id === (int) $account->id()) {
        return AccessResult::allowed();
      }
      return AccessResult::forbidden();
    }

    return AccessResult::forbidden();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    if ($account->hasPermission('administer dhcr backend')) {
      return AccessResult::allowed();
    }
    // Allow authenticated users to create courses; tighten later by role if needed.
    return AccessResult::allowedIf($account->isAuthenticated());
  }

}
