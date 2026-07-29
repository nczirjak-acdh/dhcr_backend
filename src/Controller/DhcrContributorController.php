<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\dhcr_backend\ListBuilder\DhcrSortableRowsTrait;
use Drupal\user\UserInterface;

final class DhcrContributorController extends ControllerBase {
  use DhcrSortableRowsTrait;

  public function contributorNetwork(): array {
    $cards = [
      [
        'title' => (string) $this->t('Invite User'),
        'icon' => 'fas fa-plus',
        'count' => NULL,
        'url' => Url::fromRoute('entity.dhcr_user_invitation.add_form')->toString(),
      ],
      [
        'title' => (string) $this->t('All Users'),
        'icon' => 'fas fa-user',
        'count' => $this->countProfiles(),
        'url' => Url::fromRoute('dhcr_backend.all_users')->toString(),
      ],
      [
        'title' => (string) $this->t('Pending Invitations'),
        'icon' => 'fas fa-ellipsis-h',
        'count' => $this->countInvitations(),
        'url' => Url::fromRoute('dhcr_backend.pending_invitations')->toString(),
      ],
    ];

    if ($this->currentUser()->hasPermission('administer dhcr global settings')) {
      $cards[] = [
        'title' => (string) $this->t('Moderators'),
        'icon' => 'fas fa-asterisk',
        'count' => $this->countProfiles(['moderator' => 1]),
        'url' => Url::fromRoute('dhcr_backend.moderators')->toString(),
      ];
    }

    return [
      '#theme' => 'dhcr_contributor_network',
      '#cards' => $cards,
      '#attached' => [
        'library' => ['dhcr_backend/admin_contributor_network'],
      ],
    ];
  }

  public function allUsers(): array {
    $rows = [];
    $storage = $this->entityTypeManager()->getStorage('dhcr_contributor_profile');
    $profiles = $storage->loadMultiple(
      $storage->getQuery()->accessCheck(FALSE)->execute()
    );

    foreach ($profiles as $profile) {
      $account = $profile->get('user')->entity;
      $institution = $profile->get('institution')->entity;
      $user_view_url = $account ? Url::fromRoute('dhcr_backend.user_view', ['user' => (int) $account->id()])->toString() : '';
      $user_edit_url = $account ? Url::fromRoute('dhcr_backend.user_edit', ['user' => (int) $account->id()])->toString() : '';

      $rows[] = [
        'id' => (string) $profile->id(),
        'view_url' => $user_view_url,
        'edit_url' => $user_edit_url,
        'last_name' => (string) ($profile->get('last_name')->value ?? ''),
        'first_name' => (string) ($profile->get('first_name')->value ?? ''),
        'email' => (string) ($profile->get('email')->value ?? ''),
        'enabled' => ((int) ($profile->get('enabled')->value ?? 0) === 1) ? 'Yes' : 'No',
        'enabled_sort' => (int) ($profile->get('enabled')->value ?? 0),
        'institution' => $institution ? (string) $institution->label() : '',
        'other_organisation' => (string) ($profile->get('other_organisation')->value ?? ''),
      ];
    }

    $rows = $this->sortRows($rows, [
      'last_name' => 'last_name',
      'first_name' => 'first_name',
      'email' => 'email',
      'enabled' => 'enabled_sort',
    ], 'last_name');

    return $this->buildContributorTable('dhcr_all_users', 'All Users', 'user', $rows, [
      'last_name' => (string) $this->t('Last Name'),
      'first_name' => (string) $this->t('First Name'),
      'email' => (string) $this->t('Email'),
      'enabled' => (string) $this->t('Account enabled'),
    ]);
  }

  public function pendingInvitations(): array {
    $rows = [];
    $storage = $this->entityTypeManager()->getStorage('dhcr_user_invitation');
    $invitations = $storage->loadMultiple(
      $storage->getQuery()->accessCheck(FALSE)->execute()
    );

    foreach ($invitations as $invitation) {
      $institution = $invitation->get('institution')->entity;
      $valid_until = (int) ($invitation->get('valid_until')->value ?? 0);
      $account = $invitation->get('user')->entity;
      $user_id = $account ? (int) $account->id() : 0;

      $rows[] = [
        'id' => (string) $invitation->id(),
        'reinvite_url' => Url::fromRoute('dhcr_backend.reinvite_user', ['dhcr_user_invitation' => $invitation->id()])->toString(),
        'view_url' => $user_id > 0 ? Url::fromRoute('dhcr_backend.user_view', ['user' => $user_id])->toString() : '',
        'edit_url' => $user_id > 0 ? Url::fromRoute('dhcr_backend.user_edit', ['user' => $user_id])->toString() : '',
        'last_name' => (string) ($invitation->get('last_name')->value ?? ''),
        'first_name' => (string) ($invitation->get('first_name')->value ?? ''),
        'email' => (string) ($invitation->get('email')->value ?? ''),
        'enabled' => ((int) ($invitation->get('account_enabled')->value ?? 0) === 1) ? 'Yes' : 'No',
        'enabled_sort' => (int) ($invitation->get('account_enabled')->value ?? 0),
        'institution' => $institution ? (string) $institution->label() : '',
        'valid_until' => $valid_until > 0 ? gmdate('Y-m-d H:i', $valid_until) . ' UTC' : '',
        'valid_until_sort' => $valid_until,
      ];
    }

    $rows = $this->sortRows($rows, [
      'last_name' => 'last_name',
      'first_name' => 'first_name',
      'email' => 'email',
      'enabled' => 'enabled_sort',
      'valid_until' => 'valid_until_sort',
    ], 'valid_until');

    return $this->buildContributorTable('dhcr_pending_invitations', 'Pending Invitations', '...', $rows, [
      'last_name' => (string) $this->t('Last Name'),
      'first_name' => (string) $this->t('First Name'),
      'email' => (string) $this->t('Email'),
      'enabled' => (string) $this->t('Account enabled'),
      'valid_until' => (string) $this->t('Invitation valid until'),
    ]);
  }

  public function moderators(): array {
    $rows = [];
    $storage = $this->entityTypeManager()->getStorage('dhcr_contributor_profile');
    $profiles = $storage->loadMultiple(
      $storage->getQuery()->accessCheck(FALSE)->condition('moderator', 1)->execute()
    );

    foreach ($profiles as $profile) {
      $account = $profile->get('user')->entity;
      $institution = $profile->get('institution')->entity;
      $user_view_url = $account ? Url::fromRoute('dhcr_backend.user_view', ['user' => (int) $account->id()])->toString() : '';
      $user_edit_url = $account ? Url::fromRoute('dhcr_backend.user_edit', ['user' => (int) $account->id()])->toString() : '';

      $rows[] = [
        'id' => (string) $profile->id(),
        'view_url' => $user_view_url,
        'edit_url' => $user_edit_url,
        'last_name' => (string) ($profile->get('last_name')->value ?? ''),
        'first_name' => (string) ($profile->get('first_name')->value ?? ''),
        'email' => (string) ($profile->get('email')->value ?? ''),
        'enabled' => ((int) ($profile->get('enabled')->value ?? 0) === 1) ? 'Yes' : 'No',
        'enabled_sort' => (int) ($profile->get('enabled')->value ?? 0),
        'institution' => $institution ? (string) $institution->label() : '',
        'other_organisation' => (string) ($profile->get('other_organisation')->value ?? ''),
      ];
    }

    $rows = $this->sortRows($rows, [
      'last_name' => 'last_name',
      'first_name' => 'first_name',
      'email' => 'email',
      'enabled' => 'enabled_sort',
    ], 'last_name');

    return $this->buildContributorTable('dhcr_moderators', 'Moderators', '*', $rows, [
      'last_name' => (string) $this->t('Last Name'),
      'first_name' => (string) $this->t('First Name'),
      'email' => (string) $this->t('Email'),
      'enabled' => (string) $this->t('Account enabled'),
    ]);
  }

  public function userView(UserInterface $user): array {
    $profile = $this->loadProfile((int) $user->id());
    $legacy = $this->loadLegacyUserData($user);
    $institution = $profile?->get('institution')->entity;
    $full_name = trim((string) ($profile?->get('first_name')->value ?? '') . ' ' . (string) ($profile?->get('last_name')->value ?? ''));
    if ($full_name === '') {
      $full_name = (string) $user->getDisplayName();
    }

    return [
      '#theme' => 'dhcr_user_details',
      '#title' => $full_name,
      '#edit_url' => Url::fromRoute('dhcr_backend.user_edit', ['user' => (int) $user->id()])->toString(),
      '#account_status' => [
        [
          'label' => (string) $this->t('Email Verified'),
          'value' => $this->yesNo((int) $legacy['email_verified'] === 1),
          'state' => ((int) $legacy['email_verified'] === 1) ? 'yes' : 'no',
        ],
        [
          'label' => (string) $this->t('Password Set'),
          'value' => $this->yesNo((int) $legacy['password_set'] === 1),
          'state' => ((int) $legacy['password_set'] === 1) ? 'yes' : 'no',
        ],
        [
          'label' => (string) $this->t('Approved'),
          'value' => $this->yesNo((int) $legacy['approved'] === 1),
          'state' => ((int) $legacy['approved'] === 1) ? 'yes' : 'no',
        ],
        [
          'label' => (string) $this->t('User account enabled'),
          'value' => $this->yesNo($user->isActive() && (int) ($profile?->get('enabled')->value ?? 1) === 1),
          'state' => ($user->isActive() && (int) ($profile?->get('enabled')->value ?? 1) === 1) ? 'yes' : 'no',
        ],
      ],
      '#details' => [
        [
          'label' => (string) $this->t('Email Address'),
          'value' => (string) ($profile?->get('email')->value ?? $user->getEmail() ?? ''),
        ],
        [
          'label' => (string) $this->t('Contributor Mailing List Subscription'),
          'value' => $this->yesNo((int) $legacy['mail_list'] === 1),
        ],
        [
          'label' => (string) $this->t('Institution'),
          'value' => $institution ? (string) $institution->label() : (string) $this->t('Empty!'),
          'state' => $institution ? '' : 'no',
        ],
        [
          'label' => (string) $this->t('About'),
          'value' => (string) $legacy['about'],
        ],
      ],
      '#roles' => [
        [
          'label' => (string) $this->t('Moderator'),
          'value' => $this->yesNo($user->hasRole('moderator')),
        ],
        [
          'label' => (string) $this->t('Moderated country'),
          'value' => $this->countryLabel((int) $legacy['country_id']) ?: '-',
        ],
        [
          'label' => (string) $this->t('Admin'),
          'value' => $this->yesNo($user->hasRole('administrator') || (int) $legacy['is_admin'] === 1),
        ],
        [
          'label' => (string) $this->t('Show as admin on contact page'),
          'value' => $this->yesNo((int) $legacy['user_admin'] === 1),
        ],
        [
          'label' => (string) $this->t('Show this user in the National Moderators List'),
          'value' => $this->yesNo((int) $legacy['national_moderator_list'] === 1),
        ],
      ],
      '#attached' => [
        'library' => ['dhcr_backend/admin_user_edit'],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['user:' . $user->id()],
      ],
    ];
  }

  public function reinviteUser($dhcr_user_invitation): array {
    $invitation = $this->entityTypeManager()->getStorage('dhcr_user_invitation')->load($dhcr_user_invitation);
    if ($invitation) {
      $invitation->set('valid_until', strtotime('+24 hours'));
      $invitation->save();
      $this->messenger()->addStatus($this->t('Invitation renewed for %mail.', [
        '%mail' => (string) $invitation->get('email')->value,
      ]));
    }

    return $this->redirect('dhcr_backend.pending_invitations');
  }

  private function yesNo(bool $value): string {
    return $value ? (string) $this->t('Yes') : (string) $this->t('No');
  }

  private function loadProfile(int $uid) {
    $storage = $this->entityTypeManager()->getStorage('dhcr_contributor_profile');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user', $uid)
      ->range(0, 1)
      ->execute();

    return $ids ? $storage->load((int) reset($ids)) : NULL;
  }

  private function loadLegacyUserData(UserInterface $user): array {
    $uid = (int) $user->id();
    $module = 'dhcr_backend';
    $user_data = \Drupal::service('user.data');

    $password_set = $user_data->get($module, $uid, 'legacy_password_set');
    if ($password_set === NULL) {
      $password_set = $user->getPassword() ? 1 : 0;
    }

    return [
      'email_verified' => (int) ($user_data->get($module, $uid, 'legacy_email_verified') ?? 0),
      'password_set' => (int) $password_set,
      'approved' => (int) ($user_data->get($module, $uid, 'legacy_approved') ?? 0),
      'mail_list' => (int) ($user_data->get($module, $uid, 'legacy_mail_list') ?? 0),
      'about' => (string) ($user_data->get($module, $uid, 'legacy_about') ?? ''),
      'is_admin' => (int) ($user_data->get($module, $uid, 'legacy_is_admin') ?? ($user->hasRole('administrator') ? 1 : 0)),
      'user_admin' => (int) ($user_data->get($module, $uid, 'legacy_user_admin') ?? 0),
      'national_moderator_list' => (int) ($user_data->get($module, $uid, 'legacy_national_moderator_list') ?? 0),
      'country_id' => (int) ($user_data->get($module, $uid, 'legacy_country_id') ?? 0),
    ];
  }

  private function countryLabel(int $country_id): string {
    if ($country_id <= 0) {
      return '';
    }
    $country = $this->entityTypeManager()->getStorage('dhcr_country')->load($country_id);
    return $country ? (string) $country->label() : '';
  }

  private function buildContributorTable(string $theme, string $heading, string $icon, array $rows, array $sort_columns): array {
    $total = count($rows);

    return [
      '#theme' => $theme,
      '#heading' => $heading,
      '#icon' => $icon,
      // Provide the complete result set so client-side search can match
      // across all records, not only a pre-sliced server page.
      '#rows' => $rows,
      '#sort_links' => $this->buildSortLinks($sort_columns, array_key_first($sort_columns)),
      '#pager' => [
        'current' => 1,
        'total_pages' => 1,
        'total_items' => $total,
        'previous_url' => '',
        'next_url' => '',
      ],
      '#attached' => [
        'library' => ['dhcr_backend/admin_contributor_network'],
      ],
    ];
  }

  private function countProfiles(array $conditions = []): int {
    $query = $this->entityTypeManager()->getStorage('dhcr_contributor_profile')->getQuery()->accessCheck(FALSE);
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }
    return (int) $query->count()->execute();
  }

  private function countInvitations(): int {
    return (int) $this->entityTypeManager()
      ->getStorage('dhcr_user_invitation')
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }
}
