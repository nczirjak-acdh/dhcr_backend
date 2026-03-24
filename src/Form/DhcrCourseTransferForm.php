<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Component\Utility\Html;
use Drupal\dhcr_backend\Entity\Course;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class DhcrCourseTransferForm extends FormBase {

  private EntityTypeManagerInterface $entityTypeManager;

  private AccountProxyInterface $currentUser;

  private Connection $database;

  public static function create(ContainerInterface $container): self {
    $instance = new self();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentUser = $container->get('current_user');
    $instance->database = $container->get('database');
    return $instance;
  }

  public function getFormId(): string {
    return 'dhcr_backend_course_transfer_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?Course $dhcr_course = NULL): array {
    if (!$dhcr_course) {
      $form['message'] = [
        '#markup' => '<p>' . $this->t('Course not found.') . '</p>',
      ];
      return $form;
    }

    $is_global_admin = $this->currentUser->hasPermission('administer dhcr global settings');
    $moderated_country_id = $is_global_admin ? 0 : $this->resolveCurrentUserCountryId();
    $course_country_id = $this->resolveCourseCountryId($dhcr_course);

    if (
      !$is_global_admin &&
      ($moderated_country_id <= 0 || ($course_country_id > 0 && $course_country_id !== $moderated_country_id))
    ) {
      throw new AccessDeniedHttpException();
    }

    $owner_options = $this->buildOwnerOptions($is_global_admin ? 0 : $moderated_country_id);
    $form_state->set('allowed_owner_ids', array_map('intval', array_keys($owner_options)));

    [$owner_name, $owner_mail] = $this->resolveOwnerDetails((int) $dhcr_course->getOwnerId());
    $course_name = Html::escape((string) $dhcr_course->label());
    $lecturer_name = Html::escape((string) ($dhcr_course->get('contact_name')->value ?? ''));
    $lecturer_mail = Html::escape((string) ($dhcr_course->get('contact_mail')->value ?? ''));
    $owner_name = Html::escape($owner_name);
    $owner_mail = Html::escape($owner_mail);

    $form['#attached']['library'][] = 'dhcr_backend/admin_all_courses';
    $form['#attributes']['class'][] = 'dhcr-course-transfer-form';
    $form['#attributes']['class'][] = 'column-responsive';
    $form['#attributes']['class'][] = 'column-80';
    $form['#attributes']['class'][] = 'courses';
    $form['#attributes']['class'][] = 'content';

    $form['course_id'] = [
      '#type' => 'hidden',
      '#value' => (int) $dhcr_course->id(),
    ];

    $form['transfer_heading'] = [
      '#type' => 'markup',
      '#markup' => '<h2><span class="glyphicon glyphicon-transfer"></span>&nbsp;&nbsp;&nbsp;' . $this->t('Transfer Course') . '</h2>',
      '#weight' => -100,
    ];

    $form['transfer_info'] = [
      '#type' => 'markup',
      '#markup' => '<p style="padding: 1.2em; border: 1px solid #ffbf01; border-radius: 5px; background-color: #ffe59cf7; font-weight: bolder; color: #6d7278; font-size: 0.8rem; margin-bottom: 2em; ">'
        . $this->t('The <strong><u><i>course owner</i></u></strong> is the person who has entered the course in the registry, can see it in My Courses and receives the reminder emails. These details are not public visible.')
        . '<br>&nbsp;<br>'
        . $this->t('The <strong><u><i>lecturer</i></u></strong> name and email address are shown public in the course detail page.')
        . '<br>&nbsp;<br>'
        . $this->t('<u>Please mind checking / updating both of them, when transfering a course.</u>')
        . '<br>&nbsp;<br>'
        . $this->t('Moderators can transfer courses between users within their moderated country. Admins can transfer between all users.')
        . '<br>&nbsp;<br>'
        . $this->t('Transfering a course does not update the course. If the course is outdated you need to update it separately.')
        . '</p>',
      '#weight' => -99,
    ];

    $form['course_details_heading'] = [
      '#type' => 'markup',
      '#markup' => '<strong><u>' . $this->t('Course details') . '</u></strong>',
      '#weight' => -98,
    ];

    $form['course_details'] = [
      '#type' => 'markup',
      '#weight' => -97,
      '#markup' => '<table><tbody>'
        . '<tr><td style="padding: 5px">' . $this->t('Course name:') . '</td><td style="padding: 5px">' . $course_name . '</td></tr>'
        . '<tr><td style="padding: 5px">' . $this->t('Course <u>owner</u> name:') . '</td><td style="padding: 5px">' . $owner_name . '</td></tr>'
        . '<tr><td style="padding: 5px">' . $this->t('Course <u>owner</u> email address:') . '</td><td style="padding: 5px">' . $owner_mail . '</td></tr>'
        . '<tr><td style="padding: 5px">' . $this->t('Lecturer name:') . '</td><td style="padding: 5px">' . $lecturer_name . '</td></tr>'
        . '<tr><td style="padding: 5px">' . $this->t('Lecturer email address:') . '</td><td style="padding: 5px">' . $lecturer_mail . '</td></tr>'
        . '</tbody></table>',
    ];

    $form['transfer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Transfer Course'),
      '#weight' => 0,
    ];

    $form['transfer']['user_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select new course owner by lastname'),
      '#options' => $owner_options,
      '#empty_option' => '',
      '#required' => TRUE,
      '#disabled' => empty($owner_options),
      '#default_value' => isset($owner_options[(int) $dhcr_course->getOwnerId()]) ? (int) $dhcr_course->getOwnerId() : '',
    ];
    if (empty($owner_options)) {
      $form['transfer']['user_id']['#description'] = $this->t('No eligible users found for transfer.');
    }

    $form['transfer']['lecturer_help'] = [
      '#type' => 'markup',
      '#markup' => '<p><strong>' . $this->t('Check / update lecturer details:') . '</strong></p>',
    ];

    $form['transfer']['contact_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lecturer Name'),
      '#default_value' => $lecturer_name,
      '#maxlength' => 255,
    ];

    $form['transfer']['contact_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lecturer Email Address'),
      '#default_value' => $lecturer_mail,
      '#maxlength' => 255,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Transfer Course'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $course_id = (int) $form_state->getValue('course_id');
    $new_owner_id = (int) $form_state->getValue('user_id');

    if ($course_id <= 0) {
      $form_state->setErrorByName('user_id', $this->t('Course not found.'));
      return;
    }

    if ($new_owner_id <= 0) {
      $form_state->setErrorByName('user_id', $this->t('Please select a new course owner.'));
      return;
    }

    $allowed_owner_ids = (array) $form_state->get('allowed_owner_ids');
    if (!in_array($new_owner_id, $allowed_owner_ids, TRUE)) {
      $form_state->setErrorByName('user_id', $this->t('Selected user cannot be assigned for this transfer.'));
      return;
    }

    $new_owner = $this->entityTypeManager->getStorage('user')->load($new_owner_id);
    if (!$new_owner instanceof UserInterface) {
      $form_state->setErrorByName('user_id', $this->t('Selected user was not found.'));
      return;
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $course_id = (int) $form_state->getValue('course_id');
    $new_owner_id = (int) $form_state->getValue('user_id');

    /** @var \Drupal\dhcr_backend\Entity\Course|null $course */
    $course = $this->entityTypeManager->getStorage('dhcr_course')->load($course_id);
    if (!$course instanceof Course) {
      $this->messenger()->addError($this->t('Course not found.'));
      return;
    }

    $is_global_admin = $this->currentUser->hasPermission('administer dhcr global settings');
    if (!$is_global_admin) {
      $moderated_country_id = $this->resolveCurrentUserCountryId();
      $course_country_id = $this->resolveCourseCountryId($course);
      if (
        $moderated_country_id <= 0 ||
        ($course_country_id > 0 && $course_country_id !== $moderated_country_id)
      ) {
        throw new AccessDeniedHttpException();
      }
    }

    $course->set('uid', $new_owner_id);
    $course->set('contact_name', trim((string) $form_state->getValue('contact_name')));
    $course->set('contact_mail', trim((string) $form_state->getValue('contact_mail')));
    $course->save();

    $this->messenger()->addStatus($this->t('Course transferred.'));
    $form_state->setRedirect('entity.dhcr_course.collection');
  }

  private function resolveCurrentUserCountryId(): int {
    $uid = (int) $this->currentUser->id();
    if ($uid <= 0) {
      return 0;
    }

    $query = $this->database->select('dhcr_contributor_profile', 'cp');
    $query->fields('cp', ['institution']);
    $query->condition('cp.user', $uid);
    $query->range(0, 1);
    $institution_id = (int) ($query->execute()->fetchField() ?: 0);
    if ($institution_id <= 0) {
      return 0;
    }

    $country_query = $this->database->select('dhcr_institution', 'i');
    $country_query->fields('i', ['country']);
    $country_query->condition('i.id', $institution_id);
    return (int) ($country_query->execute()->fetchField() ?: 0);
  }

  private function resolveCourseCountryId(Course $course): int {
    $institution_country = (int) ($course->get('institution')->entity?->get('country')->target_id ?? 0);
    if ($institution_country > 0) {
      return $institution_country;
    }
    return (int) ($course->get('country')->target_id ?? 0);
  }

  /**
   * @return array<int, string>
   */
  private function buildOwnerOptions(int $country_id = 0): array {
    $query = $this->database->select('dhcr_contributor_profile', 'cp');
    $query->fields('cp', ['user', 'last_name', 'first_name', 'academic_title', 'email']);
    $query->leftJoin('dhcr_institution', 'i', 'i.id = cp.institution');
    $query->condition('cp.user', 0, '>');
    if ($country_id > 0) {
      $query->condition('i.country', $country_id);
    }
    $query->orderBy('cp.last_name', 'ASC');
    $query->orderBy('cp.first_name', 'ASC');

    $options = [];
    foreach ($query->execute() as $row) {
      $uid = (int) $row->user;
      if ($uid <= 0 || isset($options[$uid])) {
        continue;
      }

      $last_name = trim((string) $row->last_name);
      $first_name = trim((string) $row->first_name);
      $academic_title = trim((string) $row->academic_title);
      $email = trim((string) $row->email);

      $display_name = '';
      if ($last_name !== '') {
        $display_name = $last_name . ', ' . trim(($academic_title !== '' ? $academic_title . ' ' : '') . $first_name);
      }
      else {
        $display_name = trim(($academic_title !== '' ? $academic_title . ' ' : '') . $first_name);
      }
      if ($display_name === '') {
        $display_name = 'User ' . $uid;
      }

      $options[$uid] = $email !== '' ? ($display_name . ' -- ' . $email) : $display_name;
    }

    return $options;
  }

  /**
   * @return array{0: string, 1: string}
   */
  private function resolveOwnerDetails(int $uid): array {
    if ($uid <= 0) {
      return ['', ''];
    }

    $query = $this->database->select('dhcr_contributor_profile', 'cp');
    $query->fields('cp', ['last_name', 'first_name', 'academic_title', 'email']);
    $query->condition('cp.user', $uid);
    $query->range(0, 1);
    $row = $query->execute()->fetchObject();

    if ($row) {
      $last_name = trim((string) $row->last_name);
      $first_name = trim((string) $row->first_name);
      $academic_title = trim((string) $row->academic_title);
      $email = trim((string) $row->email);
      $name = trim(($academic_title !== '' ? $academic_title . ' ' : '') . trim($first_name . ' ' . $last_name));
      return [$name, $email];
    }

    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    if ($user instanceof UserInterface) {
      return [$user->getDisplayName(), (string) $user->getEmail()];
    }

    return ['', ''];
  }

}
