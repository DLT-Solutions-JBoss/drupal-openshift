<?php

namespace Drupal\panels\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a form for adding a block plugin to a variant.
 */
class PanelsAddBlockForm extends PanelsBlockConfigureFormBase {

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $blockManager;

  /**
   * PanelsAddBlockForm constructor.
   *
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore factory.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $condition_manager
   *   The condition plugin manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $block_manager
   *   The block plugin manager.
   */
  public function __construct(SharedTempStoreFactory $tempstore, PluginManagerInterface $condition_manager, LanguageManagerInterface $language_manager, ContextRepositoryInterface $context_repository, PluginManagerInterface $block_manager) {
    parent::__construct($tempstore, $condition_manager, $language_manager, $context_repository);
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.shared_tempstore'),
      $container->get('plugin.manager.condition'),
      $container->get('language_manager'),
      $container->get('context.repository'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panels_add_block_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareBlock($plugin_id) {
    $block = $this->blockManager->createInstance($plugin_id);
    $block_id = $this->getVariantPlugin()->addBlock($block->getConfiguration());
    return $this->getVariantPlugin()->getBlock($block_id);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tempstore_id = NULL, $machine_name = NULL, $block_id = NULL, Request $request = NULL) {
    $form = parent::buildForm($form, $form_state, $tempstore_id, $machine_name, $block_id);
    $form['region']['#default_value'] = $request->query->get('region');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function submitText() {
    return $this->t('Add block');
  }

}
