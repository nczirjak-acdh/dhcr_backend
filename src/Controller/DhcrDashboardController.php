<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\dhcr_backend\Utility\DhcrMapConfig;
use Drupal\dhcr_backend\Utility\DhcrCourseStatusConfig;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

final class DhcrDashboardController extends ControllerBase {

  public function dashboard(): array {
    $current_user = $this->currentUser();
    $display_name = $current_user->getDisplayName();
    $is_global_admin = $current_user->hasPermission('administer dhcr global settings');
    $is_moderator_backend = $current_user->hasPermission('administer dhcr backend');
    $role_text = $is_global_admin
      ? (string) $this->t('administrator')
      : ($is_moderator_backend ? (string) $this->t('national moderator') : (string) $this->t('contributor'));

    $pending_approval = $this->countPendingCourses();
    $expiry_candidates = $this->countCourseExpiryCandidates();

    $cards = [
      [
        'title' => (string) $this->t('Needs Attention'),
        'icon' => 'fas fa-flag',
        'url' => $this->routeOrFallback('dhcr_backend.needs_attention'),
      ],
      [
        'title' => (string) $this->t('Administrate Courses'),
        'icon' => 'fas fa-graduation-cap',
        'url' => $this->routeOrFallback('dhcr_backend.courses_admin'),
      ],
      [
        'title' => (string) $this->t('Contributor Network'),
        'icon' => 'fas fa-user',
        'url' => $this->routeOrFallback('dhcr_backend.contributor_network'),
      ],
      [
        'title' => (string) $this->t('Profile Settings'),
        'icon' => 'fas fa-cog',
        'url' => $this->routeOrFallback('entity.user.edit_form', ['user' => $current_user->id()]),
      ],
      [
        'title' => (string) $this->t('Help'),
        'icon' => 'fas fa-question-circle',
        'url' => $this->routeOrFallback('dhcr_backend.help'),
      ],
    ];

    if ($is_global_admin) {
      $cards[] = [
        'title' => (string) $this->t('Statistics'),
        'icon' => 'fas fa-chart-bar',
        'url' => $this->routeOrFallback('dhcr_backend.statistics'),
      ];
      $cards[] = [
        'title' => (string) $this->t('Category Lists'),
        'icon' => 'fas fa-list',
        'url' => $this->routeOrFallback('dhcr_backend.category_lists'),
      ];
    }

    return [
      '#theme' => 'dhcr_dashboard',
      '#greeting' => $this->t('Hello @name, thanks for contributing to the DHCR as @role.', [
        '@name' => $display_name,
        '@role' => $role_text,
      ]),
      '#pending_approval' => $pending_approval,
      '#expiry_candidates' => $expiry_candidates,
      '#cards' => $cards,
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['dhcr_course_list'],
      ],
      '#attached' => [
        'library' => ['dhcr_backend/admin_dashboard'],
      ],
    ];
  }

  public function coursesAdmin(): array {
    $current_user = $this->currentUser();
    $is_global_admin = $current_user->hasPermission('administer dhcr global settings');
    $all_courses = $this->countAdminVisibleCourses();
    $my_courses = $this->countCourses(['uid' => (int) $current_user->id()]);
    $external_resources = $this->countEntities('dhcr_external_resource');

    $cards = [
      [
        'title' => (string) $this->t('Add Course'),
        'icon' => 'fas fa-plus',
        'count' => NULL,
        'url' => $this->routeOrFallback('entity.dhcr_course.add_form'),
      ],
      [
        'title' => (string) $this->t('All Courses'),
        'icon' => 'fas fa-list-alt',
        'count' => $all_courses,
        'url' => $this->routeOrFallback('entity.dhcr_course.collection'),
      ],
      [
        'title' => (string) $this->t('My Courses'),
        'icon' => 'fas fa-graduation-cap',
        'count' => $my_courses,
        'url' => $this->routeOrFallback('entity.dhcr_course.collection'),
      ],
    ];

    if ($is_global_admin) {
      $cards[] = [
        'title' => (string) $this->t('External Resources'),
        'icon' => 'fas fa-th-list',
        'count' => $external_resources,
        'url' => $this->routeOrFallback('entity.dhcr_external_resource.collection'),
      ];
    }

    return [
      '#theme' => 'dhcr_courses_admin',
      '#cards' => $cards,
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['dhcr_course_list'],
      ],
      '#attached' => [
        'library' => ['dhcr_backend/admin_courses'],
      ],
    ];
  }

  public function categoryLists(): array {
    $cards = [
      [
        'title' => (string) $this->t('Cities'),
        'icon' => 'fas fa-city',
        'count' => $this->countEntities('dhcr_city'),
        'url' => $this->routeOrFallback('entity.dhcr_city.collection'),
      ],
      [
        'title' => (string) $this->t('Institutions'),
        'icon' => 'fas fa-book',
        'count' => $this->countEntities('dhcr_institution'),
        'url' => $this->routeOrFallback('entity.dhcr_institution.collection'),
      ],
      [
        'title' => (string) $this->t('Translations'),
        'icon' => 'fas fa-language',
        'count' => $this->countEntities('dhcr_invite_translation'),
        'url' => $this->routeOrFallback('entity.dhcr_invite_translation.collection'),
      ],
      [
        'title' => (string) $this->t('Log Entries'),
        'icon' => 'fas fa-folder-open',
        'count' => NULL,
        'url' => $this->routeOrFallback('entity.dhcr_log_entry.collection'),
      ],
      [
        'title' => (string) $this->t('Languages'),
        'icon' => 'fas fa-language',
        'count' => $this->countEntities('dhcr_language'),
        'url' => $this->routeOrFallback('entity.dhcr_language.collection'),
      ],
      [
        'title' => (string) $this->t('Countries'),
        'icon' => 'fas fa-flag',
        'count' => $this->countEntities('dhcr_country'),
        'url' => $this->routeOrFallback('entity.dhcr_country.collection'),
      ],
      [
        'title' => (string) $this->t('FAQ Questions'),
        'icon' => 'fas fa-question-circle',
        'count' => $this->countEntities('dhcr_faq_question'),
        'url' => $this->routeOrFallback('entity.dhcr_faq_question.collection'),
      ],
    ];

    return [
      '#theme' => 'dhcr_category_lists',
      '#cards' => $cards,
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['dhcr_course_list'],
      ],
      '#attached' => [
        'library' => ['dhcr_backend/admin_category_lists'],
      ],
    ];
  }

  public function help(): array {
    $cards = [
      [
        'title' => (string) $this->t('Users, Access and Workflows'),
        'icon' => 'fas fa-wrench',
        'url' => $this->routeOrFallback('dhcr_backend.all_users'),
      ],
      [
        'title' => (string) $this->t('Moderator FAQ'),
        'icon' => 'fas fa-list-alt',
        'url' => $this->routeOrFallback('dhcr_backend.help_moderator_faq'),
      ],
    ];

    if ($this->currentUser()->hasPermission('administer dhcr global settings')) {
      array_unshift($cards, [
        'title' => (string) $this->t('Contributor FAQ'),
        'icon' => 'fas fa-graduation-cap',
        'url' => $this->routeOrFallback('entity.dhcr_faq_question.collection'),
      ]);
    }

    return [
      '#theme' => 'dhcr_help',
      '#cards' => $cards,
      '#attached' => [
        'library' => ['dhcr_backend/admin_dashboard'],
      ],
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];
  }

  public function moderatorFaq(): array {
    $storage = $this->entityTypeManager()->getStorage('dhcr_faq_question');
    $items = [];

    try {
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('category', 'moderator')
        ->condition('published', 1)
        ->sort('sort_order', 'ASC')
        ->sort('id', 'ASC')
        ->execute();

      if ($ids) {
        foreach ($storage->loadMultiple($ids) as $entity) {
          $link_item = $entity->get('link_url')->first();
          $items[] = [
            'id' => (int) $entity->id(),
            'question' => (string) $entity->label(),
            'answer' => (string) ($entity->get('answer')->value ?? ''),
            'link_title' => (string) ($entity->get('link_title')->value ?? ''),
            'link_url' => (string) ($link_item?->uri ?? ''),
          ];
        }
      }
    }
    catch (\Throwable) {
      $items = [];
    }

    return [
      '#theme' => 'dhcr_moderator_faq',
      '#items' => $items,
      '#edit_url' => $this->routeOrFallback('entity.dhcr_faq_question.collection'),
      '#attached' => [
        'library' => ['dhcr_backend/admin_help'],
      ],
      '#cache' => [
        'tags' => ['dhcr_faq_question_list'],
      ],
    ];
  }

  public function needsAttention(): array {
    $cards = [
      [
        'title' => (string) $this->t('Account Approval'),
        'icon' => 'fas fa-user',
        'count' => $this->countPendingUsers(),
        'url' => $this->routeOrFallback('dhcr_backend.account_approval'),
      ],
      [
        'title' => (string) $this->t('Course Approval'),
        'icon' => 'fas fa-graduation-cap',
        'count' => $this->countPendingCourses(),
        'url' => $this->routeOrFallback('dhcr_backend.course_approval'),
      ],
      [
        'title' => (string) $this->t('Course Expiry'),
        'icon' => 'fas fa-bell',
        'count' => $this->countCourseExpiryCandidates(),
        'url' => $this->routeOrFallback('dhcr_backend.course_expiry'),
      ],
    ];

    return [
      '#theme' => 'dhcr_needs_attention',
      '#cards' => $cards,
      '#attached' => [
        'library' => ['dhcr_backend/admin_needs_attention'],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['dhcr_course_list'],
      ],
    ];
  }

  public function accountApproval(): array {
    $rows = [];
    $user_storage = $this->entityTypeManager()->getStorage('user');
    $ids = $user_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', 1, '>')
      ->condition('status', 0)
      ->sort('uid', 'ASC')
      ->execute();

    if ($ids) {
      foreach ($user_storage->loadMultiple($ids) as $account) {
        $rows[] = [
          'name' => (string) $account->getDisplayName(),
          'mail' => (string) ($account->getEmail() ?? ''),
          'edit_url' => $account->toUrl('edit-form')->toString(),
        ];
      }
    }

    return [
      '#theme' => 'dhcr_account_approval',
      '#rows' => $rows,
      '#count' => count($rows),
      '#attached' => [
        'library' => ['dhcr_backend/admin_needs_attention'],
      ],
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];
  }

  public function courseApproval(): array {
    $entities = $this->loadCourses([
      'approved' => 0,
      'active' => 1,
      'archived' => 0,
    ]);
    $list_builder = $this->entityTypeManager()->getListBuilder('dhcr_course');

    return $list_builder->buildRenderableFromEntities($entities, [
      'heading' => (string) $this->t('Course Approval'),
      'icon' => 'school',
      'empty' => (string) $this->t('No courses in this list.'),
      'show_legend' => FALSE,
    ]);
  }

  public function courseExpiry(): array {
    $entities = $this->loadCourseExpiryCandidates();
    $list_builder = $this->entityTypeManager()->getListBuilder('dhcr_course');

    return $list_builder->buildRenderableFromEntities($entities, [
      'heading' => (string) $this->t('Course Expiry'),
      'icon' => 'bell',
      'empty' => (string) $this->t('No courses in this list.'),
      'show_legend' => TRUE,
    ]);
  }

  public function expiredCourses(): array {
    $entities = $this->loadExpiredCourses();
    $list_builder = $this->entityTypeManager()->getListBuilder('dhcr_course');

    return $list_builder->buildRenderableFromEntities($entities, [
      'heading' => (string) $this->t('Expired Courses'),
      'icon' => 'bell',
      'empty' => (string) $this->t('No expired courses found.'),
      'show_legend' => TRUE,
    ]);
  }

  private function countCourses(array $conditions): int {
    try {
      $query = $this->entityTypeManager()->getStorage('dhcr_course')->getQuery()->accessCheck(FALSE);
      foreach ($conditions as $field => $value) {
        $query->condition($field, $value);
      }
      return (int) $query->count()->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function countEntities(string $entity_type_id): int {
    try {
      return (int) $this->entityTypeManager()
        ->getStorage($entity_type_id)
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function countPendingUsers(): int {
    try {
      return (int) $this->entityTypeManager()
        ->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', 1, '>')
        ->condition('status', 0)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function countPendingCourses(): int {
    return $this->countCourses([
      'approved' => 0,
      'active' => 1,
      'archived' => 0,
    ]);
  }

  private function countAdminVisibleCourses(): int {
    try {
      return (int) $this->entityTypeManager()
        ->getStorage('dhcr_course')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('archived', 0)
        ->condition('changed', DhcrMapConfig::getCourseArchiveDateCutoff(), '>')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function countCourseExpiryCandidates(): int {
    try {
      return (int) $this->entityTypeManager()
        ->getStorage('dhcr_course')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('active', 1)
        ->condition('archived', 0)
        ->condition('changed', DhcrCourseStatusConfig::archiveDate(), '>')
        ->condition('changed', DhcrCourseStatusConfig::yellowDate(), '<')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function loadCourses(array $conditions): array {
    try {
      $storage = $this->entityTypeManager()->getStorage('dhcr_course');
      $query = $storage->getQuery()->accessCheck(FALSE);
      foreach ($conditions as $field => $value) {
        $query->condition($field, $value);
      }
      $ids = $query->execute();
      return $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Throwable) {
      return [];
    }
  }

  private function loadCourseExpiryCandidates(): array {
    try {
      $storage = $this->entityTypeManager()->getStorage('dhcr_course');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('active', 1)
        ->condition('archived', 0)
        ->condition('changed', DhcrCourseStatusConfig::archiveDate(), '>')
        ->condition('changed', DhcrCourseStatusConfig::yellowDate(), '<')
        ->execute();

      return $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Throwable) {
      return [];
    }
  }

  private function loadExpiredCourses(): array {
    try {
      $storage = $this->entityTypeManager()->getStorage('dhcr_course');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('active', 1)
        ->condition('archived', 0)
        ->condition('changed', DhcrCourseStatusConfig::archiveDate(), '>')
        ->condition('changed', DhcrCourseStatusConfig::yellowDate(), '<')
        ->execute();

      return $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Throwable) {
      return [];
    }
  }

  private function routeOrFallback(string $route_name, array $parameters = []): string {
    try {
      return Url::fromRoute($route_name, $parameters)->toString();
    }
    catch (RouteNotFoundException) {
      return Url::fromRoute('system.admin_content')->toString();
    }
  }

}
