<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\dhcr_backend\Entity\FaqQuestion;
use Drupal\dhcr_backend\Form\DhcrFaqQuestionOrderForm;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DhcrFaqQuestionController extends ControllerBase {

  private const CATEGORIES = ['public', 'contributor', 'moderator'];
  public const CONTRIBUTOR_FAQ_CONTENT_STATE_KEY = 'dhcr_backend.contributor_faq_content';
  public const CONTRIBUTOR_FAQ_FORMAT_STATE_KEY = 'dhcr_backend.contributor_faq_format';
  public const CONTRIBUTOR_FAQ_DEFAULT_FORMAT = 'full_html';
  public const CONTRIBUTOR_FAQ_DEFAULT_CONTENT = <<<'HTML'
<div class="faq content">
  <p></p>
  <h2><span class="glyphicon glyphicon-education"></span>&nbsp;&nbsp;&nbsp;Contributor FAQ</h2>
  <strong><u>Contents</u></strong>
  <ul>
    <li><a href="#question10">I have received my DH Course Registry login details. How can I add a new course?</a></li>
    <li><a href="#question11"> I have entered a course into the system, but it is not shown yet in the registry. Who approves my course?</a></li>
    <li><a href="#question12">I want to enter my course data, but some metadata (e.g. the start/end date) is not fixed yet. Can I create some kind of ‘draft course’ that can be published later, after having obtained the missing information?</a></li>
    <li><a href="#question13">My course does not take place anymore. How do I remove the course from the registry?</a></li>
  </ul>
  <p>&nbsp;</p>
  <h3 id="question10">I have received my DH Course Registry login details. How can I add a new course?</h3>
  <p>Simply navigate to the ‘administrate courses’ tile in your dashboard and click ‘add course’ to add your course data.<br>
  Make sure to check the box ‘Show course in the registry’ before you hit the submit button (i.e. ‘add course’ button).<br>
  After revision and approval by the National Moderator of your country, the course will be displayed in the registry.</p>
  <p>Note: in case you started to enter your course data, but are not sure about e.g. the start date, duration, etc., you may uncheck the box ‘show course in the registry’ and hit the ‘add course’ button. This way, you can save the draft version of your course and add all missing details later (don’t forget to check the box ‘show course in registry’ then!)</p>
  Link: <a href="https://dhcr.clarin-dariah.eu/courses/add">add your course to the Registry</a>
  <p>&nbsp;</p>
  <h3 id="question11"> I have entered a course into the system, but it is not shown yet in the registry. Who approves my course?</h3>
  <p>The National Moderator of your country needs to revise and approve your course. Please get in touch with him/her in case no action was taken for a longer time.<br>
  In case your country is not nationally moderated, please reach out to the administration board. A contact overview is provided below:</p>
  Link: <a href="https://dhcr.clarin-dariah.eu/info#contact">Contact overview</a>
  <p>&nbsp;</p>
  <h3 id="question12">I want to enter my course data, but some metadata (e.g. the start/end date) is not fixed yet. Can I create some kind of ‘draft course’ that can be published later, after having obtained the missing information?</h3>
  <p>Yes, simply uncheck the tick-box “Show course in registry”.</p>
  <p>&nbsp;</p>
  <h3 id="question13">My course does not take place anymore. How do I remove the course from the registry?</h3>
  <p>In case your course or programme does not take place anymore, edit your course details and uncheck the box ''Show course in the registry"'.</p>
  <p>&nbsp;</p>
</div>
HTML;

  public function categoryList(string $category): array {
    if (!in_array($category, self::CATEGORIES, TRUE)) {
      throw new NotFoundHttpException();
    }

    return $this->formBuilder()->getForm(DhcrFaqQuestionOrderForm::class, $category, $this->categoryTitle($category));
  }

  public function contributorContent(): array {
    $state = \Drupal::state();
    $content = (string) $state->get(self::CONTRIBUTOR_FAQ_CONTENT_STATE_KEY, self::CONTRIBUTOR_FAQ_DEFAULT_CONTENT);
    $format = (string) $state->get(self::CONTRIBUTOR_FAQ_FORMAT_STATE_KEY, self::CONTRIBUTOR_FAQ_DEFAULT_FORMAT);

    return [
      '#theme' => 'dhcr_contributor_faq_content',
      '#edit_url' => Url::fromRoute('dhcr_backend.help_contributor_faq_edit')->toString(),
      '#content' => [
        '#type' => 'processed_text',
        '#text' => $content,
        '#format' => $format,
      ],
      '#attached' => [
        'library' => ['dhcr_backend/admin_faq_question'],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  public function view(FaqQuestion $dhcr_faq_question): array {
    $category = (string) ($dhcr_faq_question->get('category')->value ?? '');
    $allowed_values = $dhcr_faq_question->getFieldDefinition('category')->getSetting('allowed_values') ?? [];
    $answer = $dhcr_faq_question->get('answer')->first();
    $link = $dhcr_faq_question->get('link_url')->first();
    $link_url = (string) ($link?->uri ?? '');
    $link_title = (string) ($dhcr_faq_question->get('link_title')->value ?: $link?->title ?: $link_url);
    $published = (int) ($dhcr_faq_question->get('published')->value ?? 0) === 1;

    return [
      '#theme' => 'dhcr_faq_question_details',
      '#title' => (string) $dhcr_faq_question->label(),
      '#edit_url' => $dhcr_faq_question->toUrl('edit-form')->toString(),
      '#category' => (string) ($allowed_values[$category] ?? $category),
      '#published' => $published,
      '#answer' => [
        '#type' => 'processed_text',
        '#text' => (string) ($answer?->value ?? ''),
        '#format' => (string) ($answer?->format ?? 'plain_text'),
      ],
      '#link_title' => $link_title,
      '#link_url' => $link_url,
      '#attached' => [
        'library' => ['dhcr_backend/admin_faq_question'],
      ],
      '#cache' => [
        'tags' => $dhcr_faq_question->getCacheTags(),
      ],
    ];
  }

  private function categoryTitle(string $category): string {
    return match ($category) {
      'public' => (string) $this->t('Public Questions'),
      'contributor' => (string) $this->t('Contributor Questions'),
      'moderator' => (string) $this->t('Moderator Questions'),
      default => '',
    };
  }

}
