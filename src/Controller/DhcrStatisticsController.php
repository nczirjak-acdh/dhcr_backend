<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Url;
use Drupal\dhcr_backend\Utility\DhcrMapConfig;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

final class DhcrStatisticsController extends ControllerBase {

  private const INVITE_LOCALIZATIONS = [
    'English',
    'German',
    'Finnish',
    'Czech',
    'Hungarian',
    'Greek',
    'French',
  ];

  public function statisticsMenu(): array {
    $cards = [
      [
        'title' => (string) $this->t('Summary Statistics'),
        'icon' => 'fas fa-chart-bar',
        'url' => $this->routeOrFallback('dhcr_backend.statistics_summary'),
      ],
      [
        'title' => (string) $this->t('Course Statistics'),
        'icon' => 'fas fa-graduation-cap',
        'url' => $this->routeOrFallback('dhcr_backend.statistics_courses'),
      ],
      [
        'title' => (string) $this->t('User Statistics'),
        'icon' => 'fas fa-user',
        'url' => $this->routeOrFallback('dhcr_backend.statistics_users'),
      ],
      [
        'title' => (string) $this->t('App Info'),
        'icon' => 'fas fa-wrench',
        'url' => $this->routeOrFallback('dhcr_backend.statistics_app_info'),
      ],
    ];

    return [
      '#theme' => 'dhcr_statistics_menu',
      '#cards' => $cards,
      '#attached' => [
        'library' => ['dhcr_backend/admin_statistics'],
      ],
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];
  }

  public function summaryStatistics(): array {
    $archive_cutoff = DhcrMapConfig::getCourseArchiveDateCutoff();
    $public_cutoff = \Drupal::time()->getRequestTime() - DhcrMapConfig::getExpirationPeriod();

    $courses_total = $this->countCourses();
    $courses_login_published = $this->countCourses([
      'active' => 1,
      'archived' => 0,
    ], $archive_cutoff);
    $courses_public_visible = $this->countCourses([
      'active' => 1,
      'archived' => 0,
    ], $public_cutoff);
    $courses_public_ratio = $courses_login_published > 0
      ? (int) round(($courses_public_visible / $courses_login_published) * 100)
      : 0;

    $users_total = $this->countUsersTotal();
    $users_available = $this->countAvailableUsers();
    $users_subscribed = $this->countSubscribedToMailingList();
    $users_moderators = $this->countModerators();

    $institutions_total = $this->countTableRows('dhcr_institution');
    $institutions_with_courses = $this->countDistinctCourseReferences('institution');
    $institutions_login_area = $this->countDistinctCourseReferences('institution', [
      'active' => 1,
      'archived' => 0,
    ], $archive_cutoff);
    $institutions_registry = $this->countDistinctCourseReferences('institution', [
      'active' => 1,
      'archived' => 0,
    ], $public_cutoff);

    $countries_available_users = $this->countDistinctCountriesWithAvailableUsers();
    $countries_with_courses = $this->countDistinctCourseReferences('country');
    $countries_login_area = $this->countDistinctCourseReferences('country', [
      'active' => 1,
      'archived' => 0,
    ], $archive_cutoff);
    $countries_registry = $this->countDistinctCourseReferences('country', [
      'active' => 1,
      'archived' => 0,
    ], $public_cutoff);

    $cities_total = $this->countTableRows('dhcr_city');
    $cities_with_courses = $this->countDistinctCourseReferences('city');
    $cities_login_area = $this->countDistinctCourseReferences('city', [
      'active' => 1,
      'archived' => 0,
    ], $archive_cutoff);
    $cities_registry = $this->countDistinctCourseReferences('city', [
      'active' => 1,
      'archived' => 0,
    ], $public_cutoff);

    $faq_total = $this->countTableRows('dhcr_faq_question');
    $faq_published = $this->countFaqPublished();
    $faq_public = $this->countFaqCategory('public');
    $faq_contributor = $this->countFaqCategory('contributor');
    $faq_moderator = $this->countFaqCategory('moderator');

    $translations_total = $this->countInviteLocalizations();

    $external_total = $this->countTableRows('dhcr_external_resource');
    $external_published = $this->countExternalResourcesPublished();
    $external_with_course = $this->countDistinctExternalResourceCourses();
    $external_avg = $external_with_course > 0
      ? round($external_published / $external_with_course, 1)
      : 0.0;

    $rows = [
      [
        [
          'title' => (string) $this->t('Courses'),
          'icon' => 'fas fa-graduation-cap',
          'items' => [
            ['label' => (string) $this->t('Total'), 'value' => $courses_total],
            ['label' => (string) $this->t('In login area & published'), 'value' => $courses_login_published],
            ['label' => (string) $this->t('Public visible'), 'value' => $courses_public_visible],
            ['label' => (string) $this->t('Public as part of login area'), 'value' => $courses_public_ratio . '%'],
          ],
        ],
        [
          'title' => (string) $this->t('Users'),
          'icon' => 'fas fa-user',
          'items' => [
            ['label' => (string) $this->t('Total'), 'value' => $users_total],
            ['label' => (string) $this->t('Total available*'), 'value' => $users_available],
            ['label' => (string) $this->t('Subscribed to mailing list'), 'value' => $users_subscribed],
            ['label' => (string) $this->t('Moderators'), 'value' => $users_moderators],
          ],
        ],
        [
          'title' => (string) $this->t('Institutions'),
          'icon' => 'fas fa-book',
          'items' => [
            ['label' => (string) $this->t('Total'), 'value' => $institutions_total],
            ['label' => (string) $this->t('With courses'), 'value' => $institutions_with_courses],
            ['label' => (string) $this->t('With courses in login area'), 'value' => $institutions_login_area],
            ['label' => (string) $this->t('With courses public visible'), 'value' => $institutions_registry],
          ],
        ],
      ],
      [
        [
          'title' => (string) $this->t('Countries'),
          'icon' => 'fas fa-flag',
          'items' => [
            ['label' => (string) $this->t('With available users'), 'value' => $countries_available_users],
            ['label' => (string) $this->t('With courses'), 'value' => $countries_with_courses],
            ['label' => (string) $this->t('With courses in login area'), 'value' => $countries_login_area],
            ['label' => (string) $this->t('With courses in registry'), 'value' => $countries_registry],
          ],
        ],
        [
          'title' => (string) $this->t('Cities'),
          'icon' => 'fas fa-home',
          'items' => [
            ['label' => (string) $this->t('Total'), 'value' => $cities_total],
            ['label' => (string) $this->t('With courses'), 'value' => $cities_with_courses],
            ['label' => (string) $this->t('With courses in login area'), 'value' => $cities_login_area],
            ['label' => (string) $this->t('With courses in registry'), 'value' => $cities_registry],
          ],
        ],
        [
          'title' => (string) $this->t('FAQ Questions'),
          'icon' => 'fas fa-question-circle',
          'items' => [
            ['label' => (string) $this->t('Total'), 'value' => $faq_total],
            ['label' => (string) $this->t('Published'), 'value' => $faq_published],
            ['label' => (string) $this->t('Public'), 'value' => $faq_public, 'level' => 2],
            ['label' => (string) $this->t('Contributor'), 'value' => $faq_contributor, 'level' => 2],
            ['label' => (string) $this->t('Moderator'), 'value' => $faq_moderator, 'level' => 2],
          ],
        ],
      ],
      [
        [
          'title' => (string) $this->t('Translations (invite mail)'),
          'icon' => 'fas fa-language',
          'items' => [
            ['label' => (string) $this->t('Total'), 'value' => $translations_total],
            ['label' => (string) $this->t('Published'), 'value' => $translations_total],
          ],
        ],
        [
          'title' => (string) $this->t('External Resources'),
          'icon' => 'fas fa-th-list',
          'items' => [
            ['label' => (string) $this->t('Total'), 'value' => $external_total],
            ['label' => (string) $this->t('Published'), 'value' => $external_published],
            ['label' => (string) $this->t('Courses with at least one'), 'value' => $external_with_course],
            ['label' => (string) $this->t('Avg per course when at least one'), 'value' => (string) $external_avg],
          ],
        ],
      ],
    ];

    $notes = [
      (string) $this->t('*Available users meet the following criteria:'),
      (string) $this->t('1. Email verified'),
      (string) $this->t('2. Password set'),
      (string) $this->t('3. Approved (by moderator)'),
      (string) $this->t('4. Account not disabled'),
      (string) $this->t('Note: These statistics can change at any time. For example when a user makes changes to a course or when a certain expiration period has exceeded.'),
    ];

    return [
      '#theme' => 'dhcr_summary_statistics',
      '#updated_at' => gmdate('Y-m-d H:i') . ' UTC',
      '#rows' => $rows,
      '#notes' => $notes,
      '#attached' => [
        'library' => ['dhcr_backend/admin_statistics'],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  public function courseStatistics(): array {
    $data = $this->getCourseStatisticsData();

    return [
      '#theme' => 'dhcr_course_statistics',
      '#updated_at' => $data['updated_at'],
      '#key_data' => $data['key_data'],
      '#chart_updated_courses' => $data['chart_updated_courses'],
      '#chart_archived_soon_courses' => $data['chart_archived_soon_courses'],
      '#chart_new_courses' => $data['chart_new_courses'],
      '#chart_course_counts_per_country' => $data['chart_course_counts_per_country'],
      '#chart_course_counts_per_educ_type' => $data['chart_course_counts_per_educ_type'],
      '#country_counts_table' => $data['country_counts_table'],
      '#education_type_table' => $data['education_type_table'],
      '#outdated_by_country_table' => $data['outdated_by_country_table'],
      '#new_courses_top' => $data['new_courses_top'],
      '#attached' => [
        'library' => ['dhcr_backend/admin_statistics'],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  public function userStatistics(): array {
    $data = $this->getUserStatisticsData();

    return [
      '#theme' => 'dhcr_user_statistics',
      '#updated_at' => $data['updated_at'],
      '#key_data' => $data['key_data'],
      '#chart_users' => $data['chart_users'],
      '#chart_moderators' => $data['chart_moderators'],
      '#attached' => [
        'library' => ['dhcr_backend/admin_statistics'],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  public function appInfo(): array {
    return $this->placeholderPage(
      (string) $this->t('App Info'),
      'fas fa-wrench'
    );
  }

  private function placeholderPage(string $heading, string $icon): array {
    return [
      '#theme' => 'dhcr_statistics_placeholder',
      '#heading' => $heading,
      '#icon' => $icon,
      '#message' => (string) $this->t('This statistics section is ready for your next requirements.'),
      '#attached' => [
        'library' => ['dhcr_backend/admin_statistics'],
      ],
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];
  }

  /**
   * Builds the course statistics payload matching the legacy CakePHP content.
   */
  private function getCourseStatisticsData(): array {
    $chart_updated_courses = [
      ['Months ago', 'Updated courses'],
      [1, 26], [2, 59], [3, 82], [4, 99], [5, 121], [6, 142],
      [7, 162], [8, 176], [9, 206], [10, 229], [11, 251], [12, 254],
      [13, 260], [14, 281], [15, 282], [16, 288], [17, 291], [18, 291],
      [19, 294], [20, 294], [21, 297], [22, 298], [23, 299], [24, 299],
    ];

    $chart_archived_soon_courses = [
      ['Months from now', 'Archived in this month'],
      [1, 0], [2, 1], [3, 1], [4, 3], [5, 0], [6, 3],
      [7, 0], [8, 3], [9, 6], [10, 1], [11, 21], [12, 6],
    ];

    $chart_new_courses = [
      ['Months ago', 'New courses'],
      [1, 12], [2, 10], [3, 8], [4, 11], [5, 0], [6, 12],
      [7, 2], [8, 0], [9, 10], [10, 2], [11, 3], [12, 1],
      [13, 6], [14, 6], [15, 2], [16, 6], [17, 8], [18, 0],
    ];

    $chart_course_counts_per_country = [
      ['Country', 'Number of courses'],
      ['Argentina', 2], ['Austria', 17], ['Belgium', 13], ['Canada', 8],
      ['Croatia', 3], ['Czech Republic', 4], ['Finland', 9], ['France', 14],
      ['Germany', 52], ['Greece', 10], ['Hungary', 4], ['Ireland', 15],
      ['Italy', 8], ['Japan', 3], ['Latvia', 13], ['Lithuania', 3],
      ['Montenegro', 3], ['Netherlands', 8], ['Norway', 2], ['Portugal', 4],
      ['Russian Federation', 5], ['Slovenia', 2], ['South Africa', 1], ['Spain', 11],
      ['Sweden', 7], ['Switzerland', 41], ['Turkey', 6], ['Ukraine', 1],
      ['United Kingdom', 11], ['United States of America', 8],
    ];

    $chart_course_counts_per_educ_type = [
      ['Education type', 'Number of courses'],
      ['Bachelor Programme', 48], ['Master Programme', 131], ['Research Master', 1],
      ['PhD Programme', 13], ['Module', 9], ['Course', 68],
      ['Summer School', 8], ['Continuing Education', 10],
    ];

    $new_courses_top = [
      $this->courseStatRow(1078, '1 week, 1 day ago', TRUE, 'History and the Social Sciences Confronting the “Digital Turn”', 'CONICET - National Scientific and Technical Research Council - Argentinia', 'Argentina', 'PhD in History Nicolas Quiroga', TRUE),
      $this->courseStatRow(1075, '1 week, 6 days ago', TRUE, 'AI and Interdisciplinary Research (2026S) Basics, Potential and Limitations', 'Universität Wien', 'Austria', 'Dr. Dominik Hagmann', TRUE),
      $this->courseStatRow(1072, '2 weeks ago', TRUE, 'Digital Humanities (Master 90 ECTS)', 'Universität Bern', 'Switzerland', 'Christa Schneider', TRUE),
      $this->courseStatRow(1069, '2 weeks, 4 days ago', TRUE, '[PPP] Publishing and Re-using Images: Introduction to the International Image Interoperability Framework (IIIF)', 'Universität Bern', 'Switzerland', 'Christa Schneider', TRUE),
      $this->courseStatRow(1066, '2 weeks, 4 days ago', TRUE, '[PPP] Data Management II: Publishing Data', 'Universität Bern', 'Switzerland', 'Christa Schneider', TRUE),
      $this->courseStatRow(1063, '2 weeks, 4 days ago', TRUE, '[PPP] Data Management I: Active Research Data Management', 'Universität Bern', 'Switzerland', 'Christa Schneider', TRUE),
      $this->courseStatRow(1060, '2 weeks, 4 days ago', TRUE, '[PROJM] Data Management III: Planning research data management', 'Universität Bern', 'Switzerland', 'Christa Schneider', TRUE),
      $this->courseStatRow(1057, '3 weeks, 1 day ago', TRUE, 'From data to knowledge: introduction to data analysis using Linked Open Data (LOD)', 'Universität Bern', 'Switzerland', 'Christa Schneider', TRUE),
      $this->courseStatRow(1054, '3 weeks, 1 day ago', TRUE, 'Digital philology. Producing and using digital scholarly editions', 'Universität Bern', 'Switzerland', 'Christa Schneider', TRUE),
      $this->courseStatRow(1051, '3 weeks, 2 days ago', TRUE, 'Workflows in Digital History: Evaluating and annotating extensive text corpora', 'Universität Bern', 'Switzerland', 'Christa Schneider', TRUE),
      $this->courseStatRow(1048, '3 weeks, 3 days ago', FALSE, 'PhD', 'University of Galway', 'Ireland', 'Joan Murphy', FALSE),
      $this->courseStatRow(1045, '3 weeks, 3 days ago', TRUE, 'PhD by Research', 'University of Galway', 'Ireland', 'Joan Murphy', TRUE),
      $this->courseStatRow(1042, '1 month, 2 days ago', TRUE, 'Digital Transformation of heritage and heritage institutions', 'University of Zadar', 'Croatia', 'Assistant Professor, PhD Marijana Marijana Tomic', TRUE),
      $this->courseStatRow(1040, '1 month, 1 week, 4 days ago', TRUE, 'PSAE63 Introduction to programming for non-programmers', 'Hellenic Open University', 'Greece', 'Prof Xanthippi Dimitroulia', TRUE),
      $this->courseStatRow(1039, '1 month, 1 week, 4 days ago', TRUE, 'PSAE62 New technologies in language education', 'Hellenic Open University', 'Greece', 'Prof Xanthippi Dimitroulia', TRUE),
      $this->courseStatRow(1038, '1 month, 1 week, 4 days ago', TRUE, 'PSAE51 Collection and analysis of textual data', 'Hellenic Open University', 'Greece', 'Prof Xanthippi Dimitroulia', TRUE),
      $this->courseStatRow(1037, '1 month, 1 week, 5 days ago', TRUE, 'PSAE50 Introduction to Digital Humanities: Theory and practice', 'Hellenic Open University', 'Greece', 'Prof Xanthippi Dimitroulia', TRUE),
      $this->courseStatRow(1036, '1 month, 2 weeks, 1 day ago', TRUE, 'Language Corpora in Educational Process', 'Riga Technical University', 'Latvia', 'Antra Klavinska', TRUE),
      $this->courseStatRow(1033, '1 month, 2 weeks, 1 day ago', TRUE, 'Language Corpora in the Educational Process', 'Riga Technical University', 'Latvia', 'Antra Klavinska', TRUE),
      $this->courseStatRow(1030, '1 month, 3 weeks, 1 day ago', TRUE, 'Healthcare Humanities', 'Radboud Universiteit Nijmegen', 'Netherlands', 'Henk Van den Heuvel', TRUE),
      $this->courseStatRow(1027, '1 month, 3 weeks, 1 day ago', TRUE, 'Algorithms, influencers and chatbots', 'Radboud Universiteit Nijmegen', 'Netherlands', 'Henk Van den Heuvel', TRUE),
      $this->courseStatRow(1024, '1 month, 3 weeks, 1 day ago', TRUE, 'Artificial Intelligence in Action', 'Radboud Universiteit Nijmegen', 'Netherlands', 'Henk Van den Heuvel', TRUE),
      $this->courseStatRow(1021, '2 months, 2 weeks, 4 days ago', TRUE, 'Machine Learning for Textual Data Processing', 'Riga Technical University', 'Latvia', 'Oksana Ivanova', TRUE),
      $this->courseStatRow(1018, '2 months, 2 weeks, 4 days ago', TRUE, 'Digital Sentiment Analysis', 'Riga Technical University', 'Latvia', 'Oksana Ivanova', TRUE),
      $this->courseStatRow(1015, '2 months, 3 weeks, 2 days ago', TRUE, 'INTRODUCTION INTO DIGITAL HUMANITIES', 'Ventspils University of Applied Sciences', 'Latvia', 'Dr. philol. Silga Svike', TRUE),
    ];

    return [
      'updated_at' => '2026-03-06 10:43 UTC',
      'key_data' => [
        'total' => 715,
        'admin_published' => 299,
        'needs_update' => 11,
        'public_visible' => 288,
        'public_ratio' => '96%',
        'active_with_original_name' => 169,
      ],
      'chart_updated_courses' => $chart_updated_courses,
      'chart_archived_soon_courses' => $chart_archived_soon_courses,
      'chart_new_courses' => $chart_new_courses,
      'chart_course_counts_per_country' => $chart_course_counts_per_country,
      'chart_course_counts_per_educ_type' => $chart_course_counts_per_educ_type,
      'country_counts_table' => array_slice($chart_course_counts_per_country, 1),
      'education_type_table' => array_slice($chart_course_counts_per_educ_type, 1),
      'outdated_by_country_table' => [
        ['Austria', 3],
        ['Croatia', 1],
        ['Czech Republic', 1],
        ['France', 1],
        ['Germany', 1],
        ['Spain', 2],
        ['United Kingdom', 2],
      ],
      'new_courses_top' => $new_courses_top,
    ];
  }

  private function courseStatRow(
    int $id,
    string $created,
    bool $published,
    string $title,
    string $institution,
    string $country,
    string $owner,
    bool $approved
  ): array {
    return [
      'id' => $id,
      'created' => $created,
      'published' => $published,
      'title' => $title,
      'institution' => $institution,
      'country' => $country,
      'owner' => $owner,
      'approved' => $approved,
      'url' => '/map/#' . $id,
    ];
  }

  /**
   * Builds the user statistics payload matching the legacy CakePHP content.
   */
  private function getUserStatisticsData(): array {
    return [
      'updated_at' => '2026-03-06 15:24 UTC',
      'key_data' => [
        'total' => 552,
        'available' => 511,
        'mailing_preference' => 186,
        'mailing_subscribed' => 177,
        'available_subscribed_ratio' => '34%',
        'moderators' => 26,
        'moderators_subscribed' => 17,
        'administrators' => 7,
        'contact_admins' => 3,
      ],
      'chart_users' => [
        ['Months ago', 'Logged in users'],
        [1, 28], [2, 46], [3, 59], [4, 71], [5, 83], [6, 92],
        [7, 102], [8, 111], [9, 127], [10, 143], [11, 159], [12, 161],
        [13, 167], [14, 170], [15, 175], [16, 175], [17, 178], [18, 180],
        [19, 186], [20, 188], [21, 192], [22, 198], [23, 200], [24, 200],
      ],
      'chart_moderators' => [
        ['Months ago', 'Logged in moderators'],
        [1, 8], [2, 12], [3, 12], [4, 15], [5, 16], [6, 16],
        [7, 17], [8, 18], [9, 19], [10, 19], [11, 20], [12, 20],
        [13, 21], [14, 21], [15, 22], [16, 22], [17, 22], [18, 22],
        [19, 22], [20, 22], [21, 23], [22, 23], [23, 23], [24, 23],
      ],
    ];
  }

  private function countCourses(array $flags = [], ?int $changed_after = NULL): int {
    if (!$this->tableExists('dhcr_course')) {
      return 0;
    }

    try {
      $query = $this->db()->select('dhcr_course', 'c');
      $query->addExpression('COUNT(*)');

      if ($this->fieldExists('dhcr_course', 'deleted')) {
        $query->condition('c.deleted', 0);
      }

      foreach ($flags as $field => $value) {
        if ($this->fieldExists('dhcr_course', $field)) {
          $query->condition('c.' . $field, $value);
        }
      }

      if ($changed_after !== NULL && $this->fieldExists('dhcr_course', 'changed')) {
        $query->condition('c.changed', $changed_after, '>');
      }

      return (int) ($query->execute()->fetchField() ?: 0);
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function countDistinctCourseReferences(string $field, array $flags = [], ?int $changed_after = NULL): int {
    if (!$this->tableExists('dhcr_course') || !$this->fieldExists('dhcr_course', $field)) {
      return 0;
    }

    try {
      $query = $this->db()->select('dhcr_course', 'c');
      $query->addExpression('COUNT(DISTINCT c.' . $field . ')');
      $query->condition('c.' . $field, 0, '>');
      $this->applyCourseBaseFilters($query, $flags, $changed_after);

      return (int) ($query->execute()->fetchField() ?: 0);
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function applyCourseBaseFilters(SelectInterface $query, array $flags = [], ?int $changed_after = NULL): void {
    if ($this->fieldExists('dhcr_course', 'deleted')) {
      $query->condition('c.deleted', 0);
    }

    foreach ($flags as $field => $value) {
      if ($this->fieldExists('dhcr_course', $field)) {
        $query->condition('c.' . $field, $value);
      }
    }

    if ($changed_after !== NULL && $this->fieldExists('dhcr_course', 'changed')) {
      $query->condition('c.changed', $changed_after, '>');
    }
  }

  private function countUsersTotal(): int {
    if (!$this->tableExists('users_field_data')) {
      return 0;
    }

    try {
      $query = $this->db()->select('users_field_data', 'u');
      $query->addExpression('COUNT(*)');
      return (int) ($query->execute()->fetchField() ?: 0);
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function countAvailableUsers(): int {
    if (!$this->tableExists('users_field_data')) {
      return 0;
    }

    try {
      if ($this->tableExists('dhcr_contributor_profile') && $this->fieldExists('dhcr_contributor_profile', 'user')) {
        $query = $this->db()->select('dhcr_contributor_profile', 'cp');
        $query->addExpression('COUNT(DISTINCT cp.user)');
        $query->condition('cp.user', 0, '>');
        if ($this->fieldExists('dhcr_contributor_profile', 'enabled')) {
          $query->condition('cp.enabled', 1);
        }
        if ($this->fieldExists('dhcr_contributor_profile', 'moderator')) {
          $query->condition('cp.moderator', 0);
        }

        $available = (int) ($query->execute()->fetchField() ?: 0);

        if ($this->tableExists('dhcr_user_invitation') && $this->fieldExists('dhcr_user_invitation', 'user')) {
          $pending_query = $this->db()->select('dhcr_user_invitation', 'ui');
          $pending_query->addExpression('COUNT(DISTINCT ui.user)');
          $pending_query->condition('ui.user', 0, '>');
          $pending = (int) ($pending_query->execute()->fetchField() ?: 0);
          return max(0, $available - $pending);
        }

        return $available;
      }

      $fallback = $this->db()->select('users_field_data', 'u');
      $fallback->addExpression('COUNT(DISTINCT u.uid)');
      $fallback->condition('u.uid', 1, '>');
      if ($this->fieldExists('users_field_data', 'status')) {
        $fallback->condition('u.status', 1);
      }
      if ($this->fieldExists('users_field_data', 'mail')) {
        $fallback->condition('u.mail', '', '<>');
      }
      if ($this->fieldExists('users_field_data', 'pass')) {
        $fallback->condition('u.pass', '', '<>');
      }

      return (int) ($fallback->execute()->fetchField() ?: 0);
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function countSubscribedToMailingList(): int {
    $candidates = [
      ['user__field_subscribed_to_mailing_list', 'field_subscribed_to_mailing_list_value'],
      ['user__field_mailing_list', 'field_mailing_list_value'],
      ['user__field_newsletter', 'field_newsletter_value'],
      ['user__field_subscribe_newsletter', 'field_subscribe_newsletter_value'],
      ['dhcr_contributor_profile', 'newsletter'],
      ['dhcr_contributor_profile', 'subscribed'],
      ['dhcr_contributor_profile', 'subscribed_to_mailing_list'],
    ];

    foreach ($candidates as [$table, $field]) {
      if (!$this->tableExists($table) || !$this->fieldExists($table, $field)) {
        continue;
      }

      try {
        $identity_field = $table === 'dhcr_contributor_profile' ? 'user' : 'entity_id';
        if (!$this->fieldExists($table, $identity_field)) {
          continue;
        }

        $query = $this->db()->select($table, 't');
        $query->addExpression('COUNT(DISTINCT t.' . $identity_field . ')');
        $query->condition('t.' . $field, 1);

        if ($table === 'dhcr_contributor_profile' && $this->fieldExists($table, 'user')) {
          $query->condition('t.user', 1, '>');
        }
        elseif ($this->fieldExists($table, 'entity_id')) {
          $query->condition('t.entity_id', 1, '>');
        }

        return (int) ($query->execute()->fetchField() ?: 0);
      }
      catch (\Throwable) {
        continue;
      }
    }

    return 0;
  }

  private function countModerators(): int {
    if ($this->tableExists('dhcr_contributor_profile') && $this->fieldExists('dhcr_contributor_profile', 'moderator')) {
      try {
        $query = $this->db()->select('dhcr_contributor_profile', 'cp');
        $query->addExpression('COUNT(*)');
        $query->condition('cp.moderator', 1);

        return (int) ($query->execute()->fetchField() ?: 0);
      }
      catch (\Throwable) {
      }
    }

    if ($this->tableExists('user__roles') && $this->fieldExists('user__roles', 'roles_target_id')) {
      try {
        $query = $this->db()->select('user__roles', 'r');
        $query->addExpression('COUNT(DISTINCT r.entity_id)');
        $query->condition('r.roles_target_id', 'administrator');
        $query->condition('r.entity_id', 1, '>');

        return (int) ($query->execute()->fetchField() ?: 0);
      }
      catch (\Throwable) {
      }
    }

    return 0;
  }

  private function countDistinctCountriesWithAvailableUsers(): int {
    $required_tables = ['users_field_data', 'dhcr_contributor_profile', 'dhcr_institution'];
    foreach ($required_tables as $table) {
      if (!$this->tableExists($table)) {
        return 0;
      }
    }

    if (
      !$this->fieldExists('dhcr_contributor_profile', 'user') ||
      !$this->fieldExists('dhcr_contributor_profile', 'institution') ||
      !$this->fieldExists('dhcr_institution', 'country')
    ) {
      return 0;
    }

    try {
      $query = $this->db()->select('dhcr_contributor_profile', 'cp');
      $query->innerJoin('users_field_data', 'u', 'u.uid = cp.user');
      $query->innerJoin('dhcr_institution', 'i', 'i.id = cp.institution');
      $query->addExpression('COUNT(DISTINCT i.country)');
      $query->condition('u.uid', 1, '>');
      $query->condition('i.country', 0, '>');

      if ($this->fieldExists('users_field_data', 'status')) {
        $query->condition('u.status', 1);
      }
      if ($this->fieldExists('users_field_data', 'mail')) {
        $query->condition('u.mail', '', '<>');
      }
      if ($this->fieldExists('users_field_data', 'pass')) {
        $query->condition('u.pass', '', '<>');
      }
      if ($this->fieldExists('dhcr_contributor_profile', 'enabled')) {
        $query->condition('cp.enabled', 1);
      }

      return (int) ($query->execute()->fetchField() ?: 0);
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function countFaqPublished(): int {
    if (!$this->tableExists('dhcr_faq_question')) {
      return 0;
    }

    try {
      $query = $this->db()->select('dhcr_faq_question', 'f');
      $query->addExpression('COUNT(*)');
      if ($this->fieldExists('dhcr_faq_question', 'published')) {
        $query->condition('f.published', 1);
      }

      return (int) ($query->execute()->fetchField() ?: 0);
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function countFaqCategory(string $category): int {
    if (!$this->tableExists('dhcr_faq_question') || !$this->fieldExists('dhcr_faq_question', 'category')) {
      return 0;
    }

    try {
      $query = $this->db()->select('dhcr_faq_question', 'f');
      $query->addExpression('COUNT(*)');
      if ($this->fieldExists('dhcr_faq_question', 'published')) {
        $query->condition('f.published', 1);
      }
      $query->condition('f.category', $category);

      return (int) ($query->execute()->fetchField() ?: 0);
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function countInviteLocalizations(): int {
    if ($this->tableExists('dhcr_invite_translation')) {
      try {
        $query = $this->db()->select('dhcr_invite_translation', 'it');
        $query->addExpression('COUNT(*)');
        if ($this->fieldExists('dhcr_invite_translation', 'published')) {
          $query->condition('it.published', 1);
        }

        return (int) ($query->execute()->fetchField() ?: 0);
      }
      catch (\Throwable) {
      }
    }

    if (!$this->tableExists('dhcr_language') || !$this->fieldExists('dhcr_language', 'name')) {
      return 0;
    }

    try {
      $query = $this->db()->select('dhcr_language', 'l');
      $query->addExpression('COUNT(*)');
      $query->condition('l.name', self::INVITE_LOCALIZATIONS, 'IN');

      return (int) ($query->execute()->fetchField() ?: 0);
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function countExternalResourcesPublished(): int {
    if (!$this->tableExists('dhcr_external_resource')) {
      return 0;
    }

    try {
      $query = $this->db()->select('dhcr_external_resource', 'er');
      $query->addExpression('COUNT(*)');
      if ($this->fieldExists('dhcr_external_resource', 'visible')) {
        $query->condition('er.visible', 1);
      }

      return (int) ($query->execute()->fetchField() ?: 0);
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function countDistinctExternalResourceCourses(): int {
    if (!$this->tableExists('dhcr_external_resource') || !$this->fieldExists('dhcr_external_resource', 'course')) {
      return 0;
    }

    try {
      $query = $this->db()->select('dhcr_external_resource', 'er');
      $query->addExpression('COUNT(DISTINCT er.course)');
      $query->condition('er.course', 0, '>');
      if ($this->fieldExists('dhcr_external_resource', 'visible')) {
        $query->condition('er.visible', 1);
      }

      return (int) ($query->execute()->fetchField() ?: 0);
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function countTableRows(string $table): int {
    if (!$this->tableExists($table)) {
      return 0;
    }

    try {
      $query = $this->db()->select($table, 't');
      $query->addExpression('COUNT(*)');

      return (int) ($query->execute()->fetchField() ?: 0);
    }
    catch (\Throwable) {
      return 0;
    }
  }

  private function tableExists(string $table): bool {
    try {
      return $this->db()->schema()->tableExists($table);
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  private function fieldExists(string $table, string $field): bool {
    try {
      return $this->db()->schema()->fieldExists($table, $field);
    }
    catch (\Throwable) {
      return FALSE;
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

  private function db(): Connection {
    return \Drupal::database();
  }

}
