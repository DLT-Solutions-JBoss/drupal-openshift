<?php

namespace Drupal\panels\Plugin\DisplayBuilder;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ctools\Plugin\PluginWizardInterface;
use Drupal\page_manager\ConditionAccessResolver;
use Drupal\panels\Form\LayoutChangeRegions;
use Drupal\panels\Form\LayoutChangeSettings;
use Drupal\panels\Form\LayoutPluginSelector;
use Drupal\panels\Form\PanelsContentForm;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The standard display builder for viewing a PanelsDisplayVariant.
 *
 * @DisplayBuilder(
 *   id = "standard",
 *   label = @Translation("Standard")
 * )
 */
class StandardDisplayBuilder extends DisplayBuilderBase implements PluginWizardInterface, ContainerFactoryPluginInterface {

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The condition manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $conditionManager;

  /**
   * Constructs a new PanelsDisplayVariant.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $condition_manager
   *   The condition manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextHandlerInterface $context_handler, ContextRepositoryInterface $context_repository, AccountInterface $account, PluginManagerInterface $condition_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
    $this->account = $account;
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.handler'),
      $container->get('context.repository'),
      $container->get('current_user'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * Build render arrays for each of the regions.
   *
   * @param array $regions
   *   The render array representing regions.
   * @param array $contexts
   *   The array of context objects.
   *
   * @return array
   *   An associative array, keyed by region ID, containing the render arrays
   *   representing the content of each region.
   */
  protected function buildRegions(array $regions, array $contexts, $title = NULL) {
    $build = [];
    $cacheability = CacheableMetadata::createFromRenderArray($build);
    foreach ($regions as $region => $blocks) {
      if (!$blocks) {
        continue;
      }

      $region_name = Html::getClass("block-region-$region");
      $build[$region]['#prefix'] = '<div class="' . $region_name . '">';
      $build[$region]['#suffix'] = '</div>';

      /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
      $weight = 0;
      foreach ($blocks as $block_id => $block) {
        if ($block instanceof ContextAwarePluginInterface) {
          $this->contextHandler->applyContextMapping($block, $contexts);
        }
        if ($block instanceof TitleBlockPluginInterface) {
          $block->setTitle($title);
        }
        $visibility = $this->checkVisibility($block);
        $access = $visibility->andIf($block->access($this->account, TRUE));
        $cacheability->addCacheableDependency($access);
        if ($access->isAllowed()) {
          $block_render_array = [
            '#theme' => 'block',
            '#attributes' => [],
            '#contextual_links' => [],
            '#weight' => $weight++,
            '#configuration' => $block->getConfiguration(),
            '#plugin_id' => $block->getPluginId(),
            '#base_plugin_id' => $block->getBaseId(),
            '#derivative_plugin_id' => $block->getDerivativeId(),
          ];

          // Build the block and bubble its attributes up if possible. This
          // allows modules like Quickedit to function.
          // See \Drupal\block\BlockViewBuilder::preRender() for reference.
          $content = $block->build();
          if ($content !== NULL && !Element::isEmpty($content)) {
            foreach (['#attributes', '#contextual_links'] as $property) {
              if (isset($content[$property])) {
                $block_render_array[$property] += $content[$property];
                unset($content[$property]);
              }
            }
          }

          // If the block is empty, instead of trying to render the block
          // correctly return just #cache, so that the render system knows the
          // reasons (cache contexts & tags) why this block is empty.
          if (Element::isEmpty($content)) {
            $block_render_array = [];
            $cacheable_metadata = CacheableMetadata::createFromObject($block_render_array);
            $cacheable_metadata->applyTo($block_render_array);
            if (isset($content['#cache'])) {
              $block_render_array['#cache'] += $content['#cache'];
            }
          }

          $block_render_array['content'] = $content;

          $build[$region][$block_id] = $block_render_array;
        }
      }
    }

    $cacheability->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function build(PanelsDisplayVariant $panels_display) {
    $regions = $panels_display->getRegionAssignments();
    $contexts = $panels_display->getContexts();
    $layout = $panels_display->getLayout();
    $title = $panels_display->renderPageTitle($panels_display->getPageTitle());

    $regions = $this->buildRegions($regions, $contexts, $title);
    if ($layout) {
      $regions = $layout->build($regions);
    }
    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardOperations($cached_values) {
    $operations = [];
    $operations['content'] = [
      'title' => $this->t('Content'),
      'form' => PanelsContentForm::class,
    ];
    return $operations;
  }

  /**
   * Performs a visibility check with condition plugins.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface
   *   Block plugin.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   AccessResult::allowed() if all conditions have their contexts and return
   *   TRUE; AccessResult::forbidden() otherwise.
   */
  protected function checkVisibility(BlockPluginInterface $block) {
    $configuration = $block->getConfiguration();
    if (empty($configuration['visibility'])) {
      return AccessResult::allowed();
    }
    $resolver = new ConditionAccessResolver($this->contextRepository, $this->contextHandler);
    $conditions = new ConditionPluginCollection($this->conditionManager, $configuration['visibility']);
    return $resolver->checkAccess($conditions);
  }

}
