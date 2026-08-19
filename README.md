# DHCR Backend

The `dhcr_backend` module provides the administrative interface, entities, and
country-scoped workflows used by the Digital Humanities Course Registry.

The main administration area is available under **Content → DHCR**:

`/admin/content/dhcr`

Menu items are displayed only when the current user has the required
permission. National moderators are restricted to records belonging to their
assigned country; users with `administer_dhcr_global_settings` can work across
all countries.

## Administration menu

### DHCR dashboard

- **Dashboard** — `/admin/content/dhcr`
- **Needs Attention** — `/admin/content/dhcr/needs-attention`
  - Account Approval — `/admin/content/dhcr/needs-attention/account-approval`
  - Course Approval — `/admin/content/dhcr/needs-attention/course-approval`
  - Course Expiry — `/admin/content/dhcr/needs-attention/course-expiry`
- **Administrate Courses** — `/admin/content/dhcr/courses-admin`
  - All Courses — `/admin/content/dhcr/courses-admin/courses`
  - My Courses — `/admin/content/dhcr/courses-admin/mycourses`
- **Contributor Network** — `/admin/content/dhcr/contributor-network`
  - Invite User — `/admin/content/dhcr/contributor-network/invite`
  - All Users — `/admin/content/dhcr/contributor-network/users`
  - Pending Invitations — `/admin/content/dhcr/contributor-network/pending-invitations`
  - Moderators — `/admin/content/dhcr/contributor-network/moderators`
- **Category Lists** — `/admin/content/dhcr/category-lists`
- **Help** — `/admin/content/dhcr/help`
  - Contributor FAQ — `/admin/content/dhcr/help/contributor-faq`
  - Moderator FAQ — `/admin/content/dhcr/help/moderator-faq`
  - Users, Access and Workflows — `/admin/content/dhcr/help/users-access-workflows`

### Courses

- **Courses** — `/admin/content/dhcr/courses`
  - Add course — `/admin/content/dhcr/courses/add`
  - Edit course — `/admin/content/dhcr/courses/{dhcr_course}/edit`
  - Transfer course — `/admin/content/dhcr/courses/{dhcr_course}/transfer`
- **Expired courses** — `/admin/content/dhcr/expired-courses`
- **External resources** — `/admin/content/dhcr/external-resources`
  - Add external resource — `/admin/content/dhcr/external-resources/add`

Course contributors see and maintain their own courses. National moderators can
maintain courses in their assigned country. Global administrators bypass the
country restriction.

### Institutions and cities

- **Institutions** — `/admin/content/dhcr/institutions`
  - Add institution — `/admin/content/dhcr/institutions/add`
- **Cities** — `/admin/content/dhcr/cities`
  - Add city — `/admin/content/dhcr/cities/add`

These menu points require `manage_dhcr_country_master_data`. National
moderators can only view and modify institutions and cities in their assigned
country.

### Global master data

The following menu points require `administer_dhcr_global_settings`:

- **Countries** — `/admin/content/dhcr/countries`
  - Add country — `/admin/content/dhcr/countries/add`
- **Languages** — `/admin/content/dhcr/languages`
  - Add language — `/admin/content/dhcr/languages/add`
- **Translations** — `/admin/content/dhcr/translations`
  - Add translation — `/admin/content/dhcr/translations/add`
- **Course types** — `/admin/content/dhcr/course-types`
  - Add course type — `/admin/content/dhcr/course-types/add`
- **Duration units** — `/admin/content/dhcr/duration-units`
  - Add duration unit — `/admin/content/dhcr/duration-units/add`

### FAQ administration

- **FAQ Questions** — `/admin/content/dhcr/faq-questions`
  - Public Questions — `/admin/content/dhcr/faq-questions/public`
  - Contributor Questions — `/admin/content/dhcr/faq-questions/contributor`
  - Moderator Questions — `/admin/content/dhcr/faq-questions/moderator`

FAQ administration requires `administer_dhcr_global_settings`. Published
contributor and moderator help pages have separate view permissions.

### Statistics

- **Statistics** — `/admin/content/dhcr/statistics`
  - Summary statistics — `/admin/content/dhcr/statistics/summary`
  - Course statistics — `/admin/content/dhcr/statistics/courses`
  - User statistics — `/admin/content/dhcr/statistics/users`
  - App info — `/admin/content/dhcr/statistics/app-info`

Statistics require `administer_dhcr_global_settings`.

### Email templates

- **Email templates** — `/admin/content/dhcr/email-templates`

This page manages DHCR email subjects, message bodies, localization, and
delivery defaults.

## Related Drupal administration

- Personal profile settings — `/user/{user}/edit`
- DHCR application logs — `/admin/reports/dhcr-logs`

The log page is provided by the separate `dhcr_logs` module and requires the
`administer dhcr logs` permission.

## Role overview

- **Course contributor**: create courses, manage own courses, edit personal
  profile settings, and view the Contributor FAQ.
- **National Moderator**: contributor capabilities plus country-scoped course,
  user, institution, city, approval, invitation, and review tools.
- **Administrator**: global DHCR configuration, master data, FAQ, statistics,
  moderator management, and unrestricted country access.
- **CR Administrator** (`cr_admin`): unrestricted administration of the DHCR
  modules and DHCR logs without general Drupal site-administrator access.

The definitive menu and route declarations are in
`dhcr_backend.links.menu.yml` and `dhcr_backend.routing.yml`.
