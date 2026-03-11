<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class DhcrInviteUserForm extends DhcrContentEntityForm {

  private const LOCALIZATION_NAMES = [
    'English',
    'German',
    'Finnish',
    'Czech',
    'Hungarian',
    'Greek',
    'French',
  ];

  public function __construct(
    ...$args
  ) {
    parent::__construct(...$args);
  }

  public static function create(ContainerInterface $container): static {
    return parent::create($container);
  }

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['#attached']['library'][] = 'dhcr_backend/admin_contributor_network';
    $form['#attributes']['class'][] = 'dhcr-invite-user-form';

    $form['dhcr_invite_heading'] = [
      '#type' => 'markup',
      '#weight' => -100,
      '#markup' => '<h2 class="dhcr-invite-user-form__heading">+ ' . $this->t('Invite User') . '</h2>',
    ];

    $form['dhcr_invite_intro'] = [
      '#type' => 'markup',
      '#weight' => -99,
      '#markup' => '<p class="dhcr-invite-user-form__intro">'
        . $this->t('The user will receive an email to set their password and join the DH-Courseregistry.') . '<br>'
        . $this->t('You will receive a BCC of this email.')
        . '</p>',
    ];

    if (isset($form['institution']['widget'][0]['target_id'])) {
      $form['institution']['widget'][0]['target_id']['#type'] = 'select';
      $form['institution']['widget'][0]['target_id']['#title'] = $this->t('Institution*');
      $form['institution']['widget'][0]['target_id']['#empty_option'] = $this->t('- Select institution -');
      $form['institution']['widget'][0]['target_id']['#required'] = TRUE;
    }

    if (isset($form['academic_title']['widget'][0]['value'])) {
      $form['academic_title']['widget'][0]['value']['#title'] = $this->t('Academic Title');
    }

    if (isset($form['first_name']['widget'][0]['value'])) {
      $form['first_name']['widget'][0]['value']['#title'] = $this->t('First Name*');
    }

    if (isset($form['last_name']['widget'][0]['value'])) {
      $form['last_name']['widget'][0]['value']['#title'] = $this->t('Last Name*');
    }

    if (isset($form['email']['widget'][0]['value'])) {
      $form['email']['widget'][0]['value']['#title'] = $this->t('Institutional Email Address*');
    }

    if (isset($form['localization']['widget'][0]['target_id'])) {
      $form['localization']['widget'][0]['target_id']['#type'] = 'select';
      $form['localization']['widget'][0]['target_id']['#title'] = $this->t('Choose localization*');
      $form['localization']['widget'][0]['target_id']['#options'] = $this->getLocalizationOptions();
      $form['localization']['widget'][0]['target_id']['#required'] = TRUE;
    }

    $form['dhcr_step_1'] = [
      '#type' => 'markup',
      '#weight' => -50,
      '#markup' => '<div class="dhcr-invite-user-form__step-title">' . $this->t('Step 1: Select an institution for the new user') . '</div>'
        . '<div class="dhcr-invite-user-form__step-copy">' . $this->t('Select an institution from the drop-down list. If the institution is not listed, go to @link.', [
          '@link' => $this->t('Add Institution'),
        ]) . '</div>',
    ];

    $form['dhcr_step_2'] = [
      '#type' => 'markup',
      '#weight' => 10,
      '#markup' => '<div class="dhcr-invite-user-form__step-title">' . $this->t('Step 2: Enter the personal details of the user') . '</div>',
    ];

    $form['dhcr_step_3'] = [
      '#type' => 'markup',
      '#weight' => 30,
      '#markup' => '<div class="dhcr-invite-user-form__step-title">' . $this->t('Step 3: Personalize the invitation email') . '</div>'
        . '<div class="dhcr-invite-user-form__note-title">' . $this->t('Note for non-English countries') . '</div>'
        . '<div class="dhcr-invite-user-form__step-copy">' . $this->t('Users may respond better to an invitation in their mother language. Although the interface and the metadata in the Course Registry are in English, you have the possibility to localize the invitation message.') . '</div>'
        . '<div class="dhcr-invite-user-form__preview-title">' . $this->t('Preview localized messages') . '</div>'
        . '<div class="dhcr-invite-user-form__preview-list">' . implode('<br>', array_map(static fn(string $name): string => $name, self::LOCALIZATION_NAMES)) . '</div>',
    ];

    if (isset($form['name'])) {
      $form['name']['#access'] = FALSE;
    }
    if (isset($form['user'])) {
      $form['user']['#access'] = FALSE;
    }
    if (isset($form['account_enabled'])) {
      $form['account_enabled']['#access'] = FALSE;
    }
    if (isset($form['valid_until'])) {
      $form['valid_until']['#access'] = FALSE;
    }
    if (isset($form['legacy_user_id'])) {
      $form['legacy_user_id']['#access'] = FALSE;
    }

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();

    $first_name = trim((string) $entity->get('first_name')->value);
    $last_name = trim((string) $entity->get('last_name')->value);
    $email = trim((string) $entity->get('email')->value);
    $full_name = trim($first_name . ' ' . $last_name);

    $entity->set('name', $full_name !== '' ? $full_name : $email);
    $entity->set('valid_until', strtotime('+24 hours'));
    $entity->set('account_enabled', 1);

    $account = user_load_by_mail($email);
    if (!$account) {
      $username = $full_name !== '' ? $full_name : $email;
      $account = User::create([
        'name' => $username,
        'mail' => $email,
        'status' => 1,
        'pass' => bin2hex(random_bytes(16)),
      ]);
      $account->save();
    }

    $entity->set('user', $account->id());
    $status = parent::save($form, $form_state);

    $profile_storage = $this->entityTypeManager->getStorage('dhcr_contributor_profile');
    $profile_ids = $profile_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user', $account->id())
      ->range(0, 1)
      ->execute();
    $profile = $profile_ids ? $profile_storage->load(reset($profile_ids)) : $profile_storage->create([]);

    $institution = $entity->get('institution')->target_id;
    $other_org = '';
    if ($institution) {
      $institution_entity = $this->entityTypeManager->getStorage('dhcr_institution')->load($institution);
      $other_org = $institution_entity ? (string) $institution_entity->label() : '';
    }

    $profile->set('name', $entity->label());
    $profile->set('user', $account->id());
    $profile->set('email', $email);
    $profile->set('first_name', $first_name);
    $profile->set('last_name', $last_name);
    $profile->set('academic_title', (string) $entity->get('academic_title')->value);
    $profile->set('institution', $institution);
    $profile->set('other_organisation', $other_org);
    $profile->set('enabled', 1);
    if ($profile->isNew()) {
      $profile->set('moderator', 0);
    }
    $profile->save();

    $this->messenger()->addStatus($this->t('Invitation prepared for %mail.', ['%mail' => $email]));
    $form_state->setRedirect('dhcr_backend.pending_invitations');

    return $status;
  }

  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    if (isset($actions['submit'])) {
      $actions['submit']['#value'] = $this->t('Send Invitation');
      $actions['submit']['#attributes']['class'][] = 'button--dhcr-outline';
    }
    return $actions;
  }

  private function getLocalizationOptions(): array {
    $storage = $this->entityTypeManager->getStorage('dhcr_language');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('name', self::LOCALIZATION_NAMES, 'IN')
      ->sort('name', 'ASC')
      ->execute();

    $options = [];
    foreach ($storage->loadMultiple($ids) as $language) {
      $options[(string) $language->id()] = (string) $language->label();
    }
    return $options;
  }
}
