<?php

/**
 * @file
 * Token integration for the Menu Link module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info_alter().
 */
function menu_link_token_info_alter(&$info) {
  $info['tokens']['node-field_menulink']['join-parents-path'] = [
    'name' => t('Join parents path'),
    'description' => t('The array values each cleaned by Pathauto and then joined with the slash into a string that resembles an URL.'),
    'type' => 'field_menulink',
  ];
}

/**
 * Implements hook_tokens().
 */
function menu_link_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'field_menulink' && !empty($data['node-field_menulink'])) {
    /** @var \Drupal\menu_link\Plugin\Field\MenuLinkItemList $items *//**/
    $items = $data['node-field_menulink'];
    $moduleHandler = \Drupal::service('module_handler');

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Token [node:field_menulink:join-parents-path].
        case 'join-parents-path':
          if (!$moduleHandler->moduleExists('pathauto')) {
            drupal_set_message(t('To use the token @token, pathauto must be enabled.', ['@token' => '[node:field_menulink:join-parents-path]']), 'error');
            return '';
          }
          $values = [];

          foreach ($items as $delta => $item) {
            $menu_links = [];
            $id = $items[$delta]->getMenuPluginId();
            /** @var \Drupal\Core\Menu\MenuLinkManager $menu_link_manager */
            $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
            $parent_ids = $menu_link_manager->getParentIds($id);

            // If no parents and if ID doesn't exists in the menu_tree,
            // maybe this item was added to the menu using Menu
            // Management interface.
            if (empty($parent_ids) && empty($menu_link_manager->getDefinition($id, FALSE))) {
              /** @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $node */
              $node = $items->getParent();
              $node_id = $node->getValue()->id();
              $langcode = $node->get('langcode');
              // If the item is assigned in more than one menu position or on
              // another menus, only the fist match on menu_link_content will be
              // used to build the path.
              // Should we create aliases based on other menu trees?
              /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $menu_content */
              $menu_content = current(\Drupal::entityTypeManager()
                ->getStorage('menu_link_content')
                ->loadByProperties(array('langcode' => $langcode->getLangcode(),'link__uri' => 'entity:node/' . $node_id)));
              // Maybe this node doesn't have a menu link, let's check.
              if ($menu_content) {
                $uuid = $menu_content->uuid();
                $id = 'menu_link_content:' . $uuid;
                $parent_ids = $menu_link_manager->getParentIds($id);
              }
            }

            // No parents means that this item is not assigned to a menu.
            if (!empty($parent_ids)) {
              foreach ($parent_ids as $parent_id) {
                // Excluding current item to avoid duplicates.
                if ($parent_id != $id) {
                  $menu_links[] = $menu_link_manager->createInstance($parent_id);
                }
              }
            }

            $menu_links = array_reverse($menu_links);
            /** @var  \Drupal\menu_link\Plugin\Menu\MenuLinkField $menu_link */
            foreach ($menu_links as $menu_link) {
              $value = \Drupal::service('pathauto.alias_cleaner')
                ->cleanString($menu_link->getTitle(), $options);
              $values[] = $value;
            }
          }

          $replacements[$original] = implode('/', $values);
          break;
      }
    }
  }

  return $replacements;
}

