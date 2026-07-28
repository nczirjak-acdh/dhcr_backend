<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Form\FormStateInterface;

final class DhcrExternalResourceForm extends DhcrContentEntityForm {

  private const RESOURCE_TYPE_OPTIONS = [
    '' => '',
    'Dataset' => 'Dataset',
    'Training Material' => 'Training Material',
    'Service' => 'Service',
    'Software' => 'Software',
  ];

  private const AFFILIATION_OPTIONS = [
    '' => '',
    'CLARIN' => 'CLARIN',
    'DARIAH' => 'DARIAH',
    'CLARIN & DARIAH' => 'CLARIN & DARIAH',
  ];

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['#attached']['library'][] = 'dhcr_backend/admin_external_resource';
    $form['#attributes']['class'][] = 'dhcr-external-resource-form';

    $form['dhcr_external_resource_heading'] = [
      '#type' => 'markup',
      '#weight' => -100,
      '#markup' => '<h2 class="dhcr-external-resource-form__heading">+ ' . $this->t('Add External Resource') . '</h2>',
    ];

    if (isset($form['title']['widget'][0]['value'])) {
      $form['title']['widget'][0]['value']['#title'] = $this->t('Label');
    }

    if (isset($form['course']['widget'][0]['target_id'])) {
      $form['course']['widget'][0]['target_id']['#title'] = $this->t('Course');
      $course_id = (int) \Drupal::request()->query->get('course');
      if ($course_id > 0 && !$this->getEntity()->id()) {
        $form['course']['widget'][0]['target_id']['#default_value'] = $this->entityTypeManager
          ->getStorage('dhcr_course')
          ->load($course_id);
      }
    }

    if (isset($form['resource_url']['widget'][0]['uri'])) {
      $form['resource_url']['widget'][0]['uri']['#title'] = $this->t('Url');
    }

    $this->convertTextFieldToSelect($form, 'resource_type', $this->t('Type'), self::RESOURCE_TYPE_OPTIONS);
    $this->convertTextFieldToSelect($form, 'affiliation', $this->t('Affiliation'), self::AFFILIATION_OPTIONS);

    return $form;
  }

  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    if (isset($actions['submit'])) {
      $actions['submit']['#value'] = $this->t('Save External Resource');
      $actions['submit']['#attributes']['class'][] = 'button--dhcr-outline';
    }
    return $actions;
  }

  private function convertTextFieldToSelect(array &$form, string $field_name, mixed $title, array $options): void {
    if (!isset($form[$field_name]['widget'][0]['value'])) {
      return;
    }

    $element = &$form[$field_name]['widget'][0]['value'];
    $default_value = trim((string) ($element['#default_value'] ?? ''));
    if ($default_value !== '' && !isset($options[$default_value])) {
      $options[$default_value] = $default_value;
    }

    $element['#type'] = 'select';
    $element['#title'] = $title;
    $element['#options'] = $options;
    $element['#default_value'] = $default_value;
    unset($element['#size'], $element['#maxlength'], $element['#placeholder'], $element['#autocomplete_route_name']);
  }
}
