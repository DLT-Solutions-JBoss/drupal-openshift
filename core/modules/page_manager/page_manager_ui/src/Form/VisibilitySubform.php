<?php

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;

/**
 * Provides form building, validation and submission for visibility conditions.
 *
 * @todo Move into core and refactor block module to use it.
 */
class VisibilitySubform extends ConditionSubform {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface
   */
  protected $manager;

  /**
   * The language manager
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $language;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * Create a new visibility subform helper object.
   *
   * @param \Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface $condition_manager
   *   The condition plugin manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository.
   *
   * @todo Add type-hinting for $condition_manager when #2385427 lands.
   */
  public function __construct($condition_manager, LanguageManagerInterface $language_manager, ContextRepositoryInterface $context_repository) {
    $this->manager = $condition_manager;
    $this->language = $language_manager;
    $this->contextRepository = $context_repository;
  }

  /**
   * Helper function for building the visibility UI form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form array with the visibility UI added in.
   *
   * @see \Drupal\block\BlockForm::buildVisibilityInterface()
   */
  public function build(array $form, FormStateInterface $form_state, array $configuration) {
    // Store the gathered contexts in the form state for other objects to use
    // during form building.
    // It's expected at least by \Drupal\Core\Condition\ConditionPluginBase.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    $form['visibility_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Visibility'),
      '#parents' => ['visibility_tabs'],
      '#attached' => [
        'library' => [
          'block/drupal.block',
        ],
      ],
    ];

    // @todo Allow list of conditions to be configured in
    //   https://www.drupal.org/node/2284687.
    $contexts = $this->contextRepository->getAvailableContexts();
    foreach ($this->manager->getDefinitionsForContexts($contexts) as $condition_id => $definition) {
      // Don't display the current theme condition.
      if ($condition_id == 'current_theme') {
        continue;
      }
      // Don't display the language condition until we have multiple languages.
      if ($condition_id == 'language' && !$this->language->isMultilingual()) {
        continue;
      }
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->manager->createInstance($condition_id, isset($configuration[$condition_id]) ? $configuration[$condition_id] : []);
      $form_state->set(['conditions', $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'visibility_tabs';
      $form[$condition_id] = $condition_form;
    }

    if (isset($form['node_type'])) {
      $form['node_type']['#title'] = $this->t('Content types');
      $form['node_type']['bundles']['#title'] = $this->t('Content types');
      $form['node_type']['negate']['#type'] = 'value';
      $form['node_type']['negate']['#title_display'] = 'invisible';
      $form['node_type']['negate']['#value'] = $form['node_type']['negate']['#default_value'];
    }
    if (isset($form['user_role'])) {
      $form['user_role']['#title'] = $this->t('Roles');
      unset($form['user_role']['roles']['#description']);
      $form['user_role']['negate']['#type'] = 'value';
      $form['user_role']['negate']['#value'] = $form['user_role']['negate']['#default_value'];
    }
    if (isset($form['request_path'])) {
      $form['request_path']['#title'] = $this->t('Pages');
      $form['request_path']['negate']['#type'] = 'radios';
      $form['request_path']['negate']['#default_value'] = (int) $form['request_path']['negate']['#default_value'];
      $form['request_path']['negate']['#title_display'] = 'invisible';
      $form['request_path']['negate']['#options'] = [
        $this->t('Show for the listed pages'),
        $this->t('Hide for the listed pages'),
      ];
    }
    if (isset($form['language'])) {
      $form['language']['negate']['#type'] = 'value';
      $form['language']['negate']['#value'] = $form['language']['negate']['#default_value'];
    }
    return $form;
  }

}