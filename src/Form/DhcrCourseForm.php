<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dhcr_backend\Utility\DhcrMapConfig;

final class DhcrCourseForm extends DhcrContentEntityForm {

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

    if (isset($form['start_dates']['widget'][0]['value'])) {
      $form['start_dates']['widget'][0]['value']['#title'] = $this->t('Start Date*');
      $form['start_dates']['widget'][0]['value']['#placeholder'] = $this->t('YYYY-MM-DD or multiple dates separated by semicolon');
      $form['start_dates']['#description'] = $this->t('At least one valid date in the format YYYY-MM-DD is needed. You can enter multiple dates separated by semicolon. Example: 2024-03-15;2024-06-15.');
    }
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

    if (isset($form['disciplines']['widget'][0]['value'])) {
      $form['disciplines']['widget'][0]['value']['#description'] = $this->t('Add one or more disciplines, separated by comma.');
    }

    if (isset($form['tadirah_techniques']['widget'][0]['value'])) {
      $form['tadirah_techniques']['widget'][0]['value']['#description'] = $this->t('Add one or more TaDiRAH techniques, separated by comma.');
    }

    if (isset($form['tadirah_objects']['widget'][0]['value'])) {
      $form['tadirah_objects']['widget'][0]['value']['#description'] = $this->t('Add one or more TaDiRAH objects, separated by comma.');
    }

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

}
