<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\VariantPluginAddBlockForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\page_manager\PageVariantInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a form for adding a block plugin to a variant.
 */
class VariantPluginAddBlockForm extends VariantPluginConfigureBlockFormBase {

  /**
   * The block manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a new VariantPluginFormBase.
   *
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   * @param \Drupal\Component\Plugin\PluginManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $condition_manager
   *   The condition plugin manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *  The context repository.
   */
  public function __construct(SharedTempStoreFactory $tempstore, PluginManagerInterface $block_manager, PluginManagerInterface $condition_manager = NULL, LanguageManagerInterface $language_manager = NULL, ContextRepositoryInterface $context_repository = NULL) {
    parent::__construct($tempstore, $condition_manager, $language_manager, $context_repository);
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.shared_tempstore'),
      $container->get('plugin.manager.block'),
      $container->get('plugin.manager.condition'),
      $container->get('language_manager'),
      $container->get('context.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_variant_add_block_form';
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
  public function buildForm(array $form, FormStateInterface $form_state, $block_display = NULL, $block_id = NULL, Request $request = NULL) {
    $form = parent::buildForm($form, $form_state, $block_display, $block_id);
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
