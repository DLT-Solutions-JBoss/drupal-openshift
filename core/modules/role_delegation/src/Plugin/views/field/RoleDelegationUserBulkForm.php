<?php

namespace Drupal\role_delegation\Plugin\views\field;

use Drupal\user\Plugin\views\field\UserBulkForm;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Defines a user operations bulk form element.
 *
 * @ViewsField("role_delegation_user_bulk_form")
 */
class RoleDelegationUserBulkForm extends UserBulkForm {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $entity_type = $this->getEntityType();
    // Filter the actions to only include those for this entity type.
    $this->actions = array_filter($this->actionStorage->loadMultiple(), function ($action) use ($entity_type) {
      $plugin_defenition = $action->getPluginDefinition();

      if ('user' == $action->getType() && in_array($plugin_defenition['id'], ['user_add_role_action', 'user_remove_role_action'])) {
        $collections = $action->getPluginCollections();
        $collection = reset($collections);
        $configuration = $collection->getConfiguration();

        return \Drupal::currentUser()->hasPermission("assign {$configuration['rid']} role");
      }
      else {
        return $action->getType() == $entity_type;
      }
    });
  }

}
