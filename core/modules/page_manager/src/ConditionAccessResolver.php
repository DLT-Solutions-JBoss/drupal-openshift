<?php

namespace Drupal\page_manager;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * A condition plugin access resolver that's context and cacheability aware.
 */
class ConditionAccessResolver {

  use ConditionAccessResolverTrait;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The context handler service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * Construct a new ConditionAccessResolver.
   *
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository service.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler service.
   */
  public function __construct(ContextRepositoryInterface $context_repository, ContextHandlerInterface $context_handler) {
    $this->contextRepository = $context_repository;
    $this->contextHandler = $context_handler;
  }

  /**
   * Performs access checks on the conditions.
   *
   * It's the caller's responsibility to add any cacheability metadata from the
   * conditions' configuration to the result as appropriate. For example if the
   * settings come from a configuration entity, it should be added as a
   * cacheable dependency.
   *
   * @param \Drupal\Core\Condition\ConditionInterface[]|\Traversable $conditions
   *   An array/traversible of condition plugins.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   AccessResult::allowed() if all conditions have their contexts and return
   *   TRUE; AccessResult::forbidden() otherwise.
   *
   * @see \Drupal\block\BlockAccessControlHandler::checkAccess()
   */
  public function checkAccess($conditions) {
    $missing_context = FALSE;
    foreach ($conditions as $condition_id => $condition) {
      if ($condition instanceof ContextAwarePluginInterface) {
        try {
          $contexts = $this->contextRepository->getRuntimeContexts(array_values($condition->getContextMapping()));
          $this->contextHandler->applyContextMapping($condition, $contexts);
        }
        catch (ContextException $e) {
          $missing_context = TRUE;
        }
      }
    }

    if ($missing_context) {
      // If any context is missing then we might be missing cacheable
      // metadata, and don't know based on what conditions the block is
      // accessible or not. For example, blocks that have a node type
      // condition will have a missing context on any non-node route like the
      // frontpage.
      // @todo Avoid setting max-age 0 for some or all cases, for example by
      //   treating available contexts without value differently in
      //   https://www.drupal.org/node/2521956.
      $access = AccessResult::forbidden()->setCacheMaxAge(0);
    }
    elseif ($this->resolveConditions($conditions, 'and') !== FALSE) {
      $access = AccessResult::allowed();
    }
    else {
      $access = AccessResult::forbidden();
    }

    $this->mergeCacheabilityFromConditions($access, $conditions);
    return $access;
  }

  /**
   * Merges cacheable metadata from conditions onto the access result object.
   *
   * @param \Drupal\Core\Access\AccessResult $access
   *   The access result object.
   * @param \Drupal\Core\Condition\ConditionInterface[] $conditions
   *   List of visibility conditions.
   *
   * @see \Drupal\block\BlockAccessControlHandler::mergeCacheabilityFromConditions()
   */
  protected function mergeCacheabilityFromConditions(AccessResult $access, $conditions) {
    foreach ($conditions as $condition) {
      if ($condition instanceof CacheableDependencyInterface) {
        $access->addCacheTags($condition->getCacheTags());
        $access->addCacheContexts($condition->getCacheContexts());
        $access->setCacheMaxAge(Cache::mergeMaxAges($access->getCacheMaxAge(), $condition->getCacheMaxAge()));
      }
    }
  }

}