<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Access;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Resolves and enforces a national moderator's country scope.
 */
final class DhcrCountryScope {

  public static function countryId(AccountInterface $account): int {
    if ($account->isAnonymous()) {
      return 0;
    }

    $country_id = (int) (\Drupal::service('user.data')->get('dhcr_backend', (int) $account->id(), 'legacy_country_id') ?? 0);
    if ($country_id > 0) {
      return $country_id;
    }

    $storage = \Drupal::entityTypeManager()->getStorage('dhcr_contributor_profile');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user', (int) $account->id())
      ->range(0, 1)
      ->execute();
    if (!$ids) {
      return 0;
    }

    $profile = $storage->load((int) reset($ids));
    return (int) ($profile?->get('institution')->entity?->get('country')->target_id ?? 0);
  }

  public static function entityCountryId(EntityInterface $entity): int {
    if ($entity->hasField('country')) {
      return (int) ($entity->get('country')->target_id ?? 0);
    }
    if ($entity->hasField('institution')) {
      return (int) ($entity->get('institution')->entity?->get('country')->target_id ?? 0);
    }
    return 0;
  }

  public static function userCountryId(UserInterface $user): int {
    $stored = (int) (\Drupal::service('user.data')->get('dhcr_backend', (int) $user->id(), 'legacy_country_id') ?? 0);
    if ($stored > 0) {
      return $stored;
    }
    return self::countryId($user);
  }

  public static function matchesEntity(AccountInterface $account, EntityInterface $entity): bool {
    return self::countryId($account) > 0 && self::countryId($account) === self::entityCountryId($entity);
  }

  public static function matchesUser(AccountInterface $account, UserInterface $user): bool {
    return self::countryId($account) > 0 && self::countryId($account) === self::userCountryId($user);
  }

}
