<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class DhcrFaqQuestionOrderForm extends FormBase {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
    );
  }

  public function getFormId(): string {
    return 'dhcr_faq_question_order_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $category = '', string $title = ''): array {
    $form['#attributes']['class'][] = 'dhcr-faq-questions-list';
    $form['#attributes']['class'][] = 'dhcr-searchable-table';
    $form['#attached']['library'][] = 'dhcr_backend/admin_faq_question';
    $form['category'] = [
      '#type' => 'value',
      '#value' => $category,
    ];

    $form['heading'] = [
      '#type' => 'markup',
      '#markup' => '<h2 class="dhcr-faq-questions-list__heading">' . $this->t('@title', ['@title' => $title]) . '</h2>',
    ];

    $form['add'] = [
      '#type' => 'link',
      '#title' => $this->t('Add FAQ Question'),
      '#url' => Url::fromRoute('entity.dhcr_faq_question.add_form', [], [
        'query' => ['category' => $category],
      ]),
      '#attributes' => [
        'class' => ['dhcr-faq-questions-list__add'],
      ],
    ];

    $entities = $this->loadQuestions($category);
    if ($entities === []) {
      $form['empty'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('No @category available.', ['@category' => mb_strtolower($title)]) . '</p>',
      ];
      return $form;
    }

    $form['search'] = [
      '#type' => 'markup',
      '#markup' => '<div class="dhcr-table-search"><input class="dhcr-table-search__input" type="search" placeholder="' . $this->t('Search FAQ questions') . '" data-dhcr-table-search-input></div>',
    ];

    $form['items'] = [
      '#type' => 'table',
      '#header' => [
        '',
        $this->t('Id'),
        $this->t('Sort order'),
        $this->t('Question'),
        $this->t('Published'),
        $this->t('Action'),
      ],
      '#attributes' => [
        'class' => ['dhcr-faq-questions-list__table'],
        'data-dhcr-table-search-table' => TRUE,
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'dhcr-faq-question-order-weight',
        ],
      ],
    ];

    foreach ($entities as $entity) {
      $id = (int) $entity->id();
      $form['items'][$id]['#attributes']['class'][] = 'draggable';
      $form['items'][$id]['handle'] = [
        '#plain_text' => '',
      ];
      $form['items'][$id]['id'] = [
        '#plain_text' => (string) $id,
      ];
      $form['items'][$id]['sort_order'] = [
        '#type' => 'weight',
        '#title' => $this->t('Sort order for @question', ['@question' => $entity->label()]),
        '#title_display' => 'invisible',
        '#default_value' => (int) ($entity->get('sort_order')->value ?? 0),
        '#delta' => 100,
        '#attributes' => [
          'class' => ['dhcr-faq-question-order-weight'],
        ],
      ];
      $form['items'][$id]['question'] = [
        '#plain_text' => (string) $entity->label(),
      ];
      $form['items'][$id]['published'] = [
        '#plain_text' => ((int) ($entity->get('published')->value ?? 0) === 1) ? (string) $this->t('Yes') : (string) $this->t('No'),
      ];
      $form['items'][$id]['action'] = [
        '#markup' => Link::fromTextAndUrl($this->t('View'), Url::fromRoute('dhcr_backend.faq_question_view', ['dhcr_faq_question' => $id]))->toString()
          . ' '
          . Link::fromTextAndUrl($this->t('Edit'), $entity->toUrl('edit-form'))->toString(),
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save order'),
      '#attributes' => [
        'class' => ['button--dhcr-outline', 'dhcr-faq-questions-list__save-order'],
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValue('items');
    if (!is_array($values)) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('dhcr_faq_question');
    $changed = 0;

    foreach ($values as $id => $item) {
      if (!is_array($item) || !isset($item['sort_order'])) {
        continue;
      }

      $entity = $storage->load((int) $id);
      if (!$entity) {
        continue;
      }

      $sort_order = (int) $item['sort_order'];
      if ((int) ($entity->get('sort_order')->value ?? 0) === $sort_order) {
        continue;
      }

      $entity->set('sort_order', $sort_order);
      $entity->save();
      $changed++;
    }

    $this->messenger()->addStatus($this->formatPlural($changed, 'Updated 1 FAQ question order.', 'Updated @count FAQ question orders.'));
  }

  private function loadQuestions(string $category): array {
    $storage = $this->entityTypeManager->getStorage('dhcr_faq_question');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('category', $category)
      ->sort('sort_order', 'ASC')
      ->sort('id', 'ASC')
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

}
