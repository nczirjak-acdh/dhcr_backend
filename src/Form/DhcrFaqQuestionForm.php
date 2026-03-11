<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Form\FormStateInterface;

final class DhcrFaqQuestionForm extends DhcrContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

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
}
