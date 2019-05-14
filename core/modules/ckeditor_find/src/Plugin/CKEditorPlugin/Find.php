<?php

namespace Drupal\ckeditor_find\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "find" plugin.
 *
 * @CKEditorPlugin(
 *   id = "find",
 *   label = @Translation("CKEditor Find/Replace"),
 *   module = "ckeditor_find"
 * )
 */
class Find extends CKEditorPluginBase {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  public function getFile() {
    return 'libraries/ckeditor/plugins/' . $this->getPluginId() . '/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'ckeditor_find/styles',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * Implements CKEditorPluginButtonsInterface::getButtons().
   */
  public function getButtons() {
    return array(
      'Find' => array(
        'label' => t('Find'),
        'image' => 'libraries/ckeditor/plugins/' . $this->getPluginId() . '/icons/find.png',
      ),
      'Find RTL' => array(
        'label' => t('Find RTL'),
        'image' => 'libraries/ckeditor/plugins/' . $this->getPluginId() . '/icons/find-rtl.png',
      ),
      'Replace' => array(
        'label' => t('Replace'),
        'image' => 'libraries/ckeditor/plugins/' . $this->getPluginId() . '/icons/replace.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
