<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Admin form for the reusable DHCR email templates.
 */
final class DhcrEmailTemplatesForm extends ConfigFormBase {

  private const CONFIG_NAME = 'dhcr_backend.email_templates';

  private const TEMPLATES = [
    'welcome' => ['Account welcome', '{{ first_name }}, {{ last_name }}, {{ login_url }}'],
    'email_confirmation' => ['Email confirmation', '{{ confirmation_url }}'],
    'password_reset' => ['Password reset', '{{ password_reset_url }}, {{ expires_at }}'],
    'account_approval_request' => ['New account approval request', '{{ user_name }}, {{ user_email }}, {{ about }}, {{ institution }}, {{ missing_institution_notice }}, {{ approval_expires_on }}, {{ approval_url }}'],
    'course_approval_request' => ['New course approval request', '{{ course_name }}, {{ course_type }}, {{ institution }}, {{ approval_url }}'],
    'course_reminder' => ['Course update reminder', '{{ recipient_name }}, {{ course_word }}, {{ has_or_have }}, {{ course_list }}'],
    'review_reminder' => ['GitHub review reminder', '{{ first_name }}, {{ issue_count }}, {{ issue_suffix }}, {{ issues_url }}, {{ oneliner }}'],
    'test' => ['Test email', 'No template-specific variables'],
  ];

  public function getFormId(): string {
    return 'dhcr_backend_email_templates_form';
  }

  protected function getEditableConfigNames(): array {
    return [self::CONFIG_NAME];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::CONFIG_NAME);

    $form['intro'] = [
      '#type' => 'item',
      '#markup' => '<p>' . $this->t('Edit the reusable plain-text emails sent by DHCR. The subject prefix and footer are added automatically.') . '</p>'
        . '<p>' . $this->t('Invitation and reinvitation messages are multilingual and remain editable under') . ' '
        . Link::fromTextAndUrl($this->t('Translations'), Url::fromRoute('entity.dhcr_invite_translation.collection'))->toString() . '.</p>',
    ];

    $form['delivery'] = [
      '#type' => 'details',
      '#title' => $this->t('Delivery defaults'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['delivery']['subject_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject prefix'),
      '#default_value' => (string) $config->get('subject_prefix'),
      '#required' => TRUE,
    ];
    $form['delivery']['sender'] = [
      '#type' => 'email',
      '#title' => $this->t('Sender address'),
      '#default_value' => (string) $config->get('sender'),
      '#description' => $this->t('Leave empty to use the site email address.'),
    ];
    $form['delivery']['reply_to'] = [
      '#type' => 'email',
      '#title' => $this->t('Reply-to address'),
      '#default_value' => (string) $config->get('reply_to'),
      '#description' => $this->t('Leave empty to use the sender address.'),
    ];
    $form['delivery']['default_cc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default CC addresses'),
      '#default_value' => (string) $config->get('default_cc'),
      '#description' => $this->t('Optional comma-separated email addresses.'),
    ];
    $form['delivery']['footer'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Shared footer'),
      '#default_value' => (string) $config->get('footer'),
      '#rows' => 6,
      '#description' => $this->t('Available variable: {{ site_url }}'),
    ];

    $form['templates'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Templates'),
    ];
    foreach (self::TEMPLATES as $template_id => [$label, $variables]) {
      $form[$template_id] = [
        '#type' => 'details',
        '#title' => $this->t($label),
        '#group' => 'templates',
        '#tree' => TRUE,
      ];
      $form[$template_id]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => (bool) $config->get('templates.' . $template_id . '.enabled'),
      ];
      $form[$template_id]['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => (string) $config->get('templates.' . $template_id . '.subject'),
        '#required' => TRUE,
      ];
      $form[$template_id]['body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Message body'),
        '#default_value' => (string) $config->get('templates.' . $template_id . '.body'),
        '#rows' => 14,
        '#required' => TRUE,
        '#description' => $this->t('Available variables: @variables. Variables must keep their double braces.', [
          '@variables' => $variables,
        ]),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->configFactory()->getEditable(self::CONFIG_NAME);
    foreach (['subject_prefix', 'sender', 'reply_to', 'default_cc', 'footer'] as $key) {
      $config->set($key, trim((string) $form_state->getValue(['delivery', $key])));
    }
    foreach (array_keys(self::TEMPLATES) as $template_id) {
      $config
        ->set('templates.' . $template_id . '.enabled', (bool) $form_state->getValue([$template_id, 'enabled']))
        ->set('templates.' . $template_id . '.subject', trim((string) $form_state->getValue([$template_id, 'subject'])))
        ->set('templates.' . $template_id . '.body', trim((string) $form_state->getValue([$template_id, 'body'])));
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
