<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Form\FormStateInterface;

final class DhcrFaqQuestionForm extends DhcrContentEntityForm {

  private const CATEGORY_ROUTES = [
    'public' => 'dhcr_backend.faq_questions_public',
    'contributor' => 'dhcr_backend.faq_questions_contributor',
    'moderator' => 'dhcr_backend.faq_questions_moderator',
  ];

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    $entity = $this->getEntity();

    $form['#attached']['library'][] = 'dhcr_backend/admin_faq_question';
    $form['#attributes']['class'][] = 'dhcr-faq-question-form';

    $form['dhcr_faq_question_heading'] = [
      '#type' => 'markup',
      '#weight' => -100,
      '#markup' => '<h2 class="dhcr-faq-question-form__heading">+ ' . $this->t('Add FAQ Question') . '</h2>',
    ];

    if (isset($form['title']['widget'][0]['value'])) {
      $form['title']['widget'][0]['value']['#title'] = $this->t('Question');
    }

    if (isset($form['answer']['widget'][0]['value'])) {
      $form['answer']['widget'][0]['value']['#title'] = $this->t('Answer');
    }

    if ($entity->isNew() && isset($form['category']['widget'][0]['value'])) {
      $category = (string) \Drupal::request()->query->get('category', '');
      if (isset(self::CATEGORY_ROUTES[$category])) {
        $form['category']['widget'][0]['value']['#default_value'] = $category;
      }
    }

    if (isset($form['link_url']['widget'][0]['uri'])) {
      $form['link_url']['widget'][0]['uri']['#title'] = $this->t('Link URL');
    }

    return $form;
  }

  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    if (isset($actions['submit'])) {
      $actions['submit']['#value'] = $this->t('Save FAQ Question');
      $actions['submit']['#attributes']['class'][] = 'button--dhcr-outline';
    }
    return $actions;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);

    $category = (string) ($this->getEntity()->get('category')->value ?? '');
    if (isset(self::CATEGORY_ROUTES[$category])) {
      $form_state->setRedirect(self::CATEGORY_ROUTES[$category]);
    }

    return $status;
  }
}
