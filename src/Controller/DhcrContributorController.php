<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\dhcr_backend\ListBuilder\DhcrSortableRowsTrait;

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
      [
        'title' => (string) $this->t('Moderators'),
        'icon' => 'fas fa-asterisk',
        'count' => $this->countProfiles(['moderator' => 1]),
        'url' => Url::fromRoute('dhcr_backend.moderators')->toString(),
      ],
    ];

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
      $user_edit_url = $account ? Url::fromRoute('dhcr_backend.user_edit', ['user' => (int) $account->id()])->toString() : '';

      $rows[] = [
        'id' => (string) $profile->id(),
        'view_url' => $user_edit_url,
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

      $rows[] = [
        'id' => (string) $invitation->id(),
        'reinvite_url' => Url::fromRoute('dhcr_backend.reinvite_user', ['dhcr_user_invitation' => $invitation->id()])->toString(),
        'view_url' => $invitation->toUrl('edit-form')->toString(),
        'edit_url' => $invitation->toUrl('edit-form')->toString(),
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
      $user_edit_url = $account ? Url::fromRoute('dhcr_backend.user_edit', ['user' => (int) $account->id()])->toString() : '';

      $rows[] = [
        'id' => (string) $profile->id(),
        'view_url' => $user_edit_url,
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
