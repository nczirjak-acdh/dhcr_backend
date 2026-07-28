<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dhcr_backend\Utility\DhcrMapConfig;

final class DhcrCourseForm extends DhcrContentEntityForm {

  private const START_DATE_ELEMENT = 'dhcr_start_date_calendar';

  private const MULTI_SELECT_FIELDS = [
    'disciplines' => [
      'element' => 'dhcr_disciplines_select',
      'label' => 'Disciplines',
    ],
    'tadirah_techniques' => [
      'element' => 'dhcr_tadirah_techniques_select',
      'label' => 'TaDiRAH Techniques',
    ],
    'tadirah_objects' => [
      'element' => 'dhcr_tadirah_objects_select',
      'label' => 'TaDiRAH Objects',
    ],
  ];

  private array $multiSelectOptions = [];

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['dhcr_intro'] = [
      '#type' => 'markup',
      '#weight' => -100,
      '#markup' => '<p><strong><u>' . $this->t('Please provide the metadata in English, independent from the language the course is held in.') . '</u></strong></p>',
    ];

    if (isset($form['title']['widget'][0]['value'])) {
      $form['title']['widget'][0]['value']['#title'] = $this->t('Course Name* [English only]');
      $form['title']['widget'][0]['value']['#placeholder'] = $this->t('Please provide the course name in English');
    }

    if (isset($form['description']['widget'][0]['value'])) {
      $form['description']['widget'][0]['value']['#title'] = $this->t('Description* [English only]');
      $form['description']['widget'][0]['value']['#placeholder'] = $this->t('Please add in English the general aims of the course/programme and the learning outcomes.');
    }

    if (isset($form['original_name']['widget'][0]['value'])) {
      $form['original_name']['widget'][0]['value']['#placeholder'] = $this->t('Optional: provide the original course name if not in English');
    }

    if (isset($form['original_description']['widget'][0]['value'])) {
      $form['original_description']['widget'][0]['value']['#placeholder'] = $this->t('Optional: provide the original description if not in English');
    }

    if (isset($form['ects']['widget'][0]['value'])) {
      $form['ects']['widget'][0]['value']['#placeholder'] = $this->t('Leave blank if not applicable');
      $form['ects']['#description'] = $this->t('Credit points rewarded within the European Credit Transfer and Accumulation System (ECTS).');
    }

    if (isset($form['course_url']['widget'][0]['uri'])) {
      $form['course_url']['widget'][0]['uri']['#title'] = $this->t('Course URL*');
      $form['course_url']['widget'][0]['uri']['#placeholder'] = $this->t('The public web address of the course description and syllabus');
    }

    if (isset($form['access_requirements']['widget'][0]['value'])) {
      $form['access_requirements']['widget'][0]['value']['#title'] = $this->t('Entry Requirements [English only]');
      $form['access_requirements']['widget'][0]['value']['#placeholder'] = $this->t('For instance: if you want to enroll in this MA module, you need to hold a BA degree in X, Y, Z');
    }

    $this->addStartDateCalendar($form);

    if (isset($form['start_date'])) {
      $form['start_date']['#access'] = FALSE;
    }
    if (isset($form['end_date'])) {
      $form['end_date']['#access'] = FALSE;
    }

    if (isset($form['recurring']['widget']['value'])) {
      $form['recurring']['widget']['value']['#title'] = $this->t('Recurring (Does the course start on the same date(s) next year)?');
    }

    if (isset($form['institution'])) {
      $form['institution']['#description'] = $this->t('If your institution is not listed, please contact your national moderator.');
    }

    $this->addMetadataMultiSelects($form);

    if (isset($form['lon']['widget'][0]['value'])) {
      $form['lon']['widget'][0]['value']['#type'] = 'hidden';
    }
    if (isset($form['lat']['widget'][0]['value'])) {
      $form['lat']['widget'][0]['value']['#type'] = 'hidden';
    }

    $entity = $this->getEntity();
    $lon = (float) ($entity->get('lon')->value ?? 16.377208);
    $lat = (float) ($entity->get('lat')->value ?? 48.209131);

    $form['dhcr_location_help'] = [
      '#type' => 'markup',
      '#weight' => 19,
      '#markup' => '<p><strong><u>' . $this->t('Location') . '</u></strong><br>'
        . $this->t('Coordinates can be adjusted using the map below. Move the marker to save longitude and latitude automatically.')
        . '</p>',
    ];

    $form['dhcr_map'] = [
      '#type' => 'container',
      '#weight' => 20,
      '#attributes' => ['id' => 'dhcr-course-map', 'style' => 'width: 600px; height: 450px;'],
      '#attached' => [
        'library' => ['dhcr_backend/course_form'],
        'drupalSettings' => [
          'dhcrBackend' => [
            'mapboxToken' => DhcrMapConfig::getMapboxToken(),
            'initialLon' => $lon,
            'initialLat' => $lat,
          ],
        ],
      ],
    ];

    $form['active']['#description'] = $this->t('Check this box if your course is ready to be published in the registry.');

    return $form;
  }

  public function afterBuild(array $element, FormStateInterface $form_state) {
    if ($form_state->isProcessingInput()) {
      $this->syncStartDateCalendarValue($form_state);
      $this->syncMetadataMultiSelectValues($form_state);
    }

    return parent::afterBuild($element, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->syncStartDateCalendarValue($form_state);
    $this->syncMetadataMultiSelectValues($form_state);
    return parent::validateForm($form, $form_state);
  }

  public function buildEntity(array $form, FormStateInterface $form_state) {
    $this->syncStartDateCalendarValue($form_state);
    $entity = parent::buildEntity($form, $form_state);

    $date = $this->normalizeDateValue((string) ($form_state->getValue(self::START_DATE_ELEMENT) ?? ''));
    if ($entity->hasField('start_date')) {
      $entity->set('start_date', $date !== '' ? $date : NULL);
    }

    return $entity;
  }

  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Add Course');

    $actions['submit_add_resources'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Course & Add Resources'),
      '#name' => 'submit_add_resources',
      '#button_type' => 'secondary',
    ];

    return $actions;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);

    $trigger = (string) ($form_state->getTriggeringElement()['#name'] ?? '');
    if ($trigger === 'submit_add_resources') {
      $entity = $this->getEntity();
      $form_state->setRedirect(
        'entity.dhcr_course.edit_form',
        ['dhcr_course' => $entity->id()],
        ['query' => ['add_resources' => 1]]
      );
      $this->messenger()->addStatus($this->t('Course saved. You can now add resources.'));
    }

    return $status;
  }

  private function addMetadataMultiSelects(array &$form): void {
    foreach (self::MULTI_SELECT_FIELDS as $field_name => $settings) {
      if (!isset($form[$field_name])) {
        continue;
      }

      $selected = $this->splitMetadataValues((string) ($this->getEntity()->get($field_name)->value ?? ''));
      $weight = (int) ($form[$field_name]['#weight'] ?? $form[$field_name]['widget']['#weight'] ?? 0);
      $form[$field_name]['#access'] = FALSE;

      $form[$settings['element']] = [
        '#type' => 'select',
        '#title' => $this->t($settings['label']),
        '#options' => $this->getMetadataOptions($field_name, $selected),
        '#default_value' => $selected,
        '#multiple' => TRUE,
        '#size' => 7,
        '#required' => TRUE,
        '#description' => $this->t('You can select more than one item.'),
        '#weight' => $weight,
        '#attributes' => [
          'class' => ['dhcr-course-metadata-select'],
        ],
      ];
    }
  }

  private function addStartDateCalendar(array &$form): void {
    if (!isset($form['start_dates'])) {
      return;
    }

    $weight = (int) ($form['start_dates']['#weight'] ?? $form['start_dates']['widget']['#weight'] ?? 0);
    $form['start_dates']['#access'] = FALSE;

    $form[self::START_DATE_ELEMENT] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date*'),
      '#default_value' => $this->extractFirstDate((string) ($this->getEntity()->get('start_dates')->value ?? '')),
      '#required' => TRUE,
      '#description' => $this->t('Choose the course start date.'),
      '#weight' => $weight,
      '#attributes' => [
        'class' => ['dhcr-course-start-date'],
      ],
    ];
  }

  private function syncStartDateCalendarValue(FormStateInterface $form_state): void {
    $date = $this->normalizeDateValue((string) ($form_state->getValue(self::START_DATE_ELEMENT) ?? ''));
    $form_state->setValue('start_dates', [
      [
        'value' => $date,
        '_weight' => 0,
      ],
    ]);
  }

  private function syncMetadataMultiSelectValues(FormStateInterface $form_state): void {
    foreach (self::MULTI_SELECT_FIELDS as $field_name => $settings) {
      $selected = $this->normalizeSelectedValues($form_state->getValue($settings['element'], []));
      $form_state->setValue($field_name, [
        [
          'value' => implode(', ', $selected),
          '_weight' => 0,
        ],
      ]);
    }
  }

  private function getMetadataOptions(string $field_name, array $selected): array {
    if (!isset($this->multiSelectOptions[$field_name])) {
      $values = [];
      $storage = \Drupal::entityTypeManager()->getStorage('dhcr_course');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->execute();

      foreach ($storage->loadMultiple($ids) as $course) {
        foreach ($this->splitMetadataValues((string) ($course->get($field_name)->value ?? '')) as $value) {
          $values[$value] = $value;
        }
      }

      natcasesort($values);
      $this->multiSelectOptions[$field_name] = $values;
    }

    $options = $this->multiSelectOptions[$field_name];
    foreach ($selected as $value) {
      $options[$value] = $value;
    }
    natcasesort($options);

    return $options;
  }

  private function normalizeSelectedValues(mixed $values): array {
    if (!is_array($values)) {
      return [];
    }

    $selected = [];
    foreach ($values as $value) {
      $value = trim((string) $value);
      if ($value !== '') {
        $selected[$value] = $value;
      }
    }

    return array_values($selected);
  }

  private function extractFirstDate(string $value): string {
    if (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/', $value, $matches) === 1) {
      $date = "{$matches[1]}-{$matches[2]}-{$matches[3]}";
      return $this->normalizeDateValue($date);
    }

    return '';
  }

  private function normalizeDateValue(string $value): string {
    $value = trim($value);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
      return '';
    }

    if (!checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
      return '';
    }

    return $value;
  }

  private function splitMetadataValues(string $value): array {
    $values = [];
    foreach (preg_split('/[;,\n]+/', $value) ?: [] as $part) {
      $part = trim($part);
      if ($part !== '') {
        $values[$part] = $part;
      }
    }

    return array_values($values);
  }

}
