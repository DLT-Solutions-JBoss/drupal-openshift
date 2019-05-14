<?php

namespace Drupal\menu_block\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;
use Drupal\system\Plugin\Block\SystemMenuBlock;

/**
 * Provides an extended Menu block.
 *
 * @Block(
 *   id = "menu_block",
 *   admin_label = @Translation("Menu block"),
 *   category = @Translation("Menus"),
 *   deriver = "Drupal\menu_block\Plugin\Derivative\MenuBlock"
 * )
 */
class MenuBlock extends SystemMenuBlock {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;
    $defaults = $this->defaultConfiguration();

    $form = parent::blockForm($form, $form_state);

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced options'),
      '#open' => FALSE,
      '#process' => [[get_class(), 'processMenuBlockFieldSets']],
    ];

    $form['advanced']['expand'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Expand all menu links</strong>'),
      '#default_value' => $config['expand'],
      '#description' => $this->t('All menu links that have children will "Show as expanded".'),
    ];

    $form['advanced']['expand_only_active_trails'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Expand only active tree</strong>'),
      '#default_value' => $config['expand_only_active_trails'],
      '#description' => $this->t('All menu links that are in active trails will "Show as expanded".'),
      '#states' => [
        'visible' => [
          ':input[name="settings[expand]"]' => ['checked' => TRUE],
        ]
      ]
    ];

    $menu_name = $this->getDerivativeId();
    $menus = Menu::loadMultiple([$menu_name]);
    $menus[$menu_name] = $menus[$menu_name]->label();

    /** @var \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_selector */
    $menu_parent_selector = \Drupal::service('menu.parent_form_selector');
    if (strpos($config['parent'], 'active_trail') === FALSE) {
      $form['advanced']['parent'] = $menu_parent_selector->parentSelectElement($config['parent'], '', $menus);
    }
    else {
      $form['advanced']['parent'] = [
        '#type' => 'select',
        '#options' => $menu_parent_selector->getParentSelectOptions('', $menus),
        '#default_value' => $config['parent'],
      ];
    }

    $form['advanced']['parent']['#options'] += [
      $menu_name . ':active_trail' => $this->t('<Active trail>')->render(),
      $menu_name . ':active_trail_parent' => $this->t('<Active trail parent>')->render(),
      $menu_name . ':active_trail_custom' => $this->t('<Active trail custom depth>')->render(),
    ];

    $form['advanced']['parent'] += [
      '#title' => $this->t('Fixed parent item'),
      '#description' => $this->t('Alter the options in “Menu levels” to be relative to the fixed parent item. The block will only contain children of the selected menu link.'),
      '#attributes' => [
        'class' => ['active-trail'],
      ],
    ];

    $form['advanced']['custom_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Set custom depth, set dynamically relative to the active trail.'),
      '#default_value' => $config['custom_level'],
      '#options' => range(0, 9),
      '#states' => [
        'visible' => [
          '.active-trail' => ['value' => $menu_name . ':active_trail_custom'],
        ],
      ],
    ];

    $form['advanced']['hide_children'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide children of parent in active trail?'),
      '#default_value' => $config['hide_children'],
      '#states' => [
        'visible' => [
          '.active-trail' => [
            ['value' => $menu_name . ':active_trail'],
            ['value' => $menu_name . ':active_trail_parent'],
            ['value' => $menu_name . ':active_trail_custom'],
          ],
        ],
      ],
    ];

    $form['style'] = [
      '#type' => 'details',
      '#title' => $this->t('HTML and style options'),
      '#open' => FALSE,
      '#process' => [[get_class(), 'processMenuBlockFieldSets']],
    ];

    $form['style']['suggestion'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Theme hook suggestion'),
      '#default_value' => $config['suggestion'],
      '#field_prefix' => '<code>menu__</code>',
      '#description' => $this->t('A theme hook suggestion can be used to override the default HTML and CSS classes for menus found in <code>menu.html.twig</code>.'),
      '#machine_name' => [
        'error' => $this->t('The theme hook suggestion must contain only lowercase letters, numbers, and underscores.'),
        'exists' => [$this, 'suggestionExists'],
      ],
    ];

    // Open the details field sets if their config is not set to defaults.
    foreach (['menu_levels', 'advanced', 'style'] as $fieldSet) {
      foreach (array_keys($form[$fieldSet]) as $field) {
        if (isset($defaults[$field]) && $defaults[$field] !== $config[$field]) {
          $form[$fieldSet]['#open'] = TRUE;
        }
      }
    }

    return $form;
  }

  /**
   * Form API callback: Processes the elements in field sets.
   *
   * Adjusts the #parents of field sets to save its children at the top level.
   */
  public static function processMenuBlockFieldSets(&$element, FormStateInterface $form_state, &$complete_form) {
    array_pop($element['#parents']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['level'] = $form_state->getValue('level');
    $this->configuration['custom_level'] = $form_state->getValue('custom_level');
    $this->configuration['hide_children'] = $form_state->getValue('hide_children');
    $this->configuration['depth'] = $form_state->getValue('depth');
    $this->configuration['expand'] = $form_state->getValue('expand');
    $this->configuration['expand_only_active_trails'] = $form_state->getValue('expand_only_active_trails');
    $this->configuration['parent'] = $form_state->getValue('parent');
    $this->configuration['suggestion'] = $form_state->getValue('suggestion');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_name = $this->getDerivativeId();
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);

    // Adjust the menu tree parameters based on the block's configuration.
    $level = $this->configuration['level'];
    $custom_level = $this->configuration['custom_level'];
    $depth = $this->configuration['depth'];
    $expand = $this->configuration['expand'];
    $expand_only_active_trails = $this->configuration['expand_only_active_trails'];
    $parent = $this->configuration['parent'];
    $hide_children = $this->configuration['hide_children'];
    $suggestion = $this->configuration['suggestion'];

    $trail_ids = $parameters->activeTrail;
    $trail_ids = array_reverse(array_filter($trail_ids));

    if ($parent == $menu_name . ':' . 'active_trail_custom') {
      $level = max($level, count($trail_ids) - $custom_level);
    }

    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    // For menu blocks with start level greater than 1, only show menu items
    // from the current active trail. Adjust the root according to the current
    // position in the menu in order to determine if we can show the subtree.
    // If we're using a fixed parent item, we'll skip this step.
    $fixed_parent_menu_link_id = str_replace($menu_name . ':', '', $parent);
    if ($level > 1 && !$fixed_parent_menu_link_id) {
      if (count($parameters->activeTrail) >= $level) {
        // Active trail array is child-first. Reverse it, and pull the new menu
        // root based on the parent of the configured start level.
        $menu_trail_ids = array_reverse(array_values($parameters->activeTrail));
        $menu_root = $menu_trail_ids[$level - 1];
        $parameters->setRoot($menu_root)->setMinDepth(1);
        if ($depth > 0) {
          $max_depth = min($level - 1 + $depth - 1, $this->menuTree->maxDepth());
          $parameters->setMaxDepth($max_depth);
        }
      }
      else {
        return [];
      }
    }

    // If expandedParents is empty, the whole menu tree is built.
    if ($expand && !$expand_only_active_trails) {
      $parameters->expandedParents = [];
    }
    // When a fixed parent item is set, root the menu tree at the given ID.
    if ($fixed_parent_menu_link_id) {
      if (strpos($fixed_parent_menu_link_id, 'custom') == FALSE) {
        // Active trail or Active trail parent option.
        if (strpos($fixed_parent_menu_link_id, 'active_trail') !== FALSE) {
          // $trail_ids = $this->menuActiveTrail->getActiveTrailIds($menu_name);
          // $trail_ids = array_reverse(array_filter($trail_ids));
          if ($fixed_parent_menu_link_id == 'active_trail') {
            $fixed_parent_menu_link_id = end($trail_ids);
          }
          // Active trail parent.
          else {
            array_pop($trail_ids);
            $fixed_parent_menu_link_id = end($trail_ids);
          }
        }
        if ($fixed_parent_menu_link_id) {
          $parameters->setRoot($fixed_parent_menu_link_id);
        }

        // If the starting level is 1, we always want the child links to appear,
        // but the requested tree may be empty if the tree does not contain the
        // active trail.
        if ($level === 1 || $level === '1') {
          // Check if the tree contains links.
           $tree = $this->menuTree->load($menu_name, $parameters);
          if (empty($tree)) {
            // Change the request to expand all children and limit the depth to
            // the immediate children of the root.
            $parameters->expandedParents = array();
            $parameters->setMinDepth(1);
            $parameters->setMaxDepth(1);
            // Re-load the tree.
            $tree = $this->menuTree->load(NULL, $parameters);
          }
        }
      }
      // end custom
    }

    // Load the tree if we haven't already.
    if (!isset($tree)) {
      $tree = $this->menuTree->load($menu_name, $parameters);
    }
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    // Hide parent children if it is set in config.
    if ($hide_children) {
      $tree = $this->hideParentChildren($tree);
    }
    $build = $this->menuTree->build($tree);

    if (!empty($build['#theme'])) {
      // Add the configuration for use in menu_block_theme_suggestions_menu().
      $build['#menu_block_configuration'] = $this->configuration;
      // Remove the menu name-based suggestion so we can control its precedence
      // better in menu_block_theme_suggestions_menu().
      $build['#theme'] = 'menu';
    }

    $build['#contextual_links']['menu'] = [
      'route_parameters' => ['menu' => $menu_name],
    ];

    return $build;
  }

  /**
   * Hide the children of the active parent.
   */
  public function hideParentChildren($tree) {
    $not_active_items = [];
    $unset = FALSE;
    if (count($tree)) {
      foreach ($tree as $id => $branch) {
        if (!$branch->inActiveTrail) {
          $not_active_items[$id] = $id;
        }
        if ($branch->inActiveTrail && $branch->hasChildren) {
          $unset = TRUE;
        }
      }
    }

    if ($unset) {
      foreach ($not_active_items as $not_active_item) {
        unset($tree[$not_active_item]);
      }
    }

    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'level' => 1,
      'custom_level' => 1,
      'hide_children' => 0,
      'depth' => 0,
      'expand' => 0,
      'expand_only_active_trails' => 0,
      'parent' => $this->getDerivativeId() . ':',
      'suggestion' => strtr($this->getDerivativeId(), '-', '_'),
    ];
  }

  /**
   * Checks for an existing theme hook suggestion.
   *
   * @return bool
   *   Returns FALSE because there is no need of validation by unique value.
   */
  public function suggestionExists() {
    return FALSE;
  }

}

