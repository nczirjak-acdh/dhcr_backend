<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dhcr_backend\Entity\UserInvitation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DhcrReinviteUserForm extends FormBase {

  private const LOCALIZATION_NAMES = [
    'English',
    'German',
    'Finnish',
    'Czech',
    'Hungarian',
    'Greek',
    'French',
  ];

  private EntityTypeManagerInterface $entityTypeManager;

  public static function create(ContainerInterface $container): self {
    $instance = new self();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  public function getFormId(): string {
    return 'dhcr_backend_reinvite_user_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?UserInvitation $dhcr_user_invitation = NULL): array {
    if (!$dhcr_user_invitation) {
      throw new NotFoundHttpException();
    }

    $institution = $dhcr_user_invitation->get('institution')->entity;
    $localization_id = (string) ($dhcr_user_invitation->get('localization')->target_id ?? '');
    $localization_options = $this->getLocalizationOptions();
    if ($localization_id === '') {
      $localization_id = (string) array_search('English', $localization_options, TRUE);
    }

    $form['#attached']['library'][] = 'dhcr_backend/admin_contributor_network';
    $form['#attributes']['class'][] = 'dhcr-reinvite-user-form';

    $form['invitation_id'] = [
      '#type' => 'hidden',
      '#value' => (int) $dhcr_user_invitation->id(),
    ];

    $form['heading'] = [
      '#type' => 'markup',
      '#weight' => -100,
      '#markup' => '<h2 class="dhcr-reinvite-user-form__heading"><i class="fas fa-retweet" aria-hidden="true"></i><span>' . $this->t('Reinvite User') . '</span></h2>',
    ];

    $form['intro'] = [
      '#type' => 'markup',
      '#weight' => -99,
      '#markup' => '<p class="dhcr-reinvite-user-form__intro">'
        . $this->t('The user will receive a <em><u>new</u></em> email to set their password and join the DH-Courseregistry.')
        . '<br>'
        . $this->t('You will receive a BCC of this email.')
        . '</p>',
    ];

    $form['reinvite_user'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Reinvite User'),
      '#weight' => 0,
      '#attributes' => [
        'class' => ['dhcr-reinvite-user-form__fieldset'],
      ],
    ];

    $form['reinvite_user']['details_heading'] = [
      '#type' => 'markup',
      '#markup' => '<h3 class="dhcr-reinvite-user-form__section-title">' . $this->t('User details') . '</h3>',
    ];

    $form['reinvite_user']['details'] = [
      '#type' => 'markup',
      '#markup' => '<dl class="dhcr-reinvite-user-form__details">'
        . $this->detailRow('Institution', $institution ? (string) $institution->label() : '')
        . $this->detailRow('Academic Title', (string) ($dhcr_user_invitation->get('academic_title')->value ?? ''))
        . $this->detailRow('First Name', (string) ($dhcr_user_invitation->get('first_name')->value ?? ''))
        . $this->detailRow('Last Name', (string) ($dhcr_user_invitation->get('last_name')->value ?? ''))
        . $this->detailRow('E-mail Address', (string) ($dhcr_user_invitation->get('email')->value ?? ''))
        . '</dl>',
    ];

    $form['reinvite_user']['localization'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose localization*'),
      '#options' => $localization_options,
      '#default_value' => $localization_id,
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send New Invitation'),
      '#button_type' => 'primary',
      '#attributes' => [
        'class' => ['button--dhcr-outline'],
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $invitation_id = (int) $form_state->getValue('invitation_id');
    $invitation = $this->entityTypeManager
      ->getStorage('dhcr_user_invitation')
      ->load($invitation_id);

    if (!$invitation instanceof UserInvitation) {
      throw new NotFoundHttpException();
    }

    $invitation->set('localization', $form_state->getValue('localization'));
    $invitation->set('valid_until', strtotime('+24 hours'));
    $invitation->save();

    $this->messenger()->addStatus($this->t('Invitation renewed for %mail.', [
      '%mail' => (string) $invitation->get('email')->value,
    ]));

    $form_state->setRedirect('dhcr_backend.pending_invitations');
  }

  private function detailRow(string $label, string $value): string {
    return '<div class="dhcr-reinvite-user-form__detail-row">'
      . '<dt>' . $this->t('@label :', ['@label' => $label]) . '</dt>'
      . '<dd>' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</dd>'
      . '</div>';
  }

  private function getLocalizationOptions(): array {
    $storage = $this->entityTypeManager->getStorage('dhcr_language');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('name', self::LOCALIZATION_NAMES, 'IN')
      ->execute();

    $languages_by_name = [];
    foreach ($storage->loadMultiple($ids) as $language) {
      $languages_by_name[(string) $language->label()] = $language;
    }

    $options = [];
    foreach (self::LOCALIZATION_NAMES as $name) {
      if (isset($languages_by_name[$name])) {
        $language = $languages_by_name[$name];
        $options[(string) $language->id()] = (string) $language->label();
      }
    }

    return $options;
  }

}
