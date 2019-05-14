<?php

namespace Drupal\wba_menu_link\Plugin\AccessControlHierarchy;

use Drupal\workbench_access\Plugin\AccessControlHierarchy\Menu;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a hierarchy based on a Menu and a menu link field.
 *
 * @AccessControlHierarchy(
 *   id = "menu_link",
 *   module = "menu_link",
 *   entity = "menu_link_content",
 *   label = @Translation("Menu (Menu link field based)"),
 *   description = @Translation("Uses a menu as an access control hierarchy, based on a menu link field.")
 * )
 */
class MenuLink extends Menu {

  /**
   * {@inheritdoc}
   */
  public function getEntityValues(EntityInterface $entity) {
    $ids = [];
    $item_list = $entity->get($this->getFields($entity->getEntityType()->id(), $entity->bundle())[0]);
    if (!empty($item_list)) {
      foreach ($item_list as $delta => $item) {
        $ids[] = $item->getMenuPluginId();
      }
    }
    return $ids;
  }

  /**
   * Helper function to get "menu_link" field name.
   */
  function getFields($entity_type, $bundle) {
    $list = [];
    $query = \Drupal::entityQuery('field_config')
      ->condition('status', 1)
      ->condition('entity_type', $entity_type)
      ->condition('bundle', $bundle)
      ->condition('field_type', 'menu_link')
      ->sort('label')
      ->execute();
    $fields = \Drupal::entityTypeManager()->getStorage('field_config')->loadMultiple(array_keys($query));
    foreach ($fields as $id => $field) {
      $list[] = $field->getName();
    }
    return $list;
  }

}

