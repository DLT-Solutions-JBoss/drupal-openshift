<?php

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Validates and submits conditions configuration subforms.
 *
 * @todo Move into core.
 */
class ConditionSubform {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * Helper function to independently validate conditions configuration.
   *
   * The condition plugins should already be added to the form state under the
   * key 'conditions'.
   *
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\block\BlockForm::validateVisibility()
   */
  public function validate(array $form, FormStateInterface $form_state) {
    // Validate visibility condition settings.
    foreach ($form_state->getValues() as $condition_id => $values) {
      // All condition plugins use 'negate' as a Boolean in their schema.
      // However, certain form elements may return it as 0/1. Cast here to
      // ensure the data is in the expected type.
      if (array_key_exists('negate', $values)) {
        $form_state->setValue([$condition_id, 'negate'], (bool) $values['negate']);
      }

      // Allow the condition to validate the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $subform_state = SubformState::createForSubform($form[$condition_id], $form, $form_state);
      $condition->validateConfigurationForm($form[$condition_id], $subform_state);
    }
  }

  /**
   * Helper function to independently submit conditions configuration.
   *
   * The condition plugins should already be added to the form state under the
   * key 'conditions'.
   *
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Component\Plugin\LazyPluginCollection $condition_collection
   *   The condition plugin collection.
   *
   * @see \Drupal\block\BlockForm::submitVisibility()
   */
  public function submit(array $form, FormStateInterface $form_state, $condition_collection) {
    foreach ($form_state->getValues() as $condition_id => $values) {
      // Allow the condition to submit the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $subform_state = SubformState::createForSubform($form[$condition_id], $form, $form_state);
      $condition->submitConfigurationForm($form[$condition_id], $subform_state);

      // Setting conditions' context mappings is the plugins' responsibility.
      // This code exists for backwards compatibility, because
      // \Drupal\Core\Condition\ConditionPluginBase::submitConfigurationForm()
      // did not set its own mappings until Drupal 8.2
      // @todo Remove the code that sets context mappings in Drupal 9.0.0.
      if ($condition instanceof ContextAwarePluginInterface) {
        $context_mapping = isset($values['context_mapping']) ? $values['context_mapping'] : [];
        $condition->setContextMapping($context_mapping);
      }

      $condition_configuration = $condition->getConfiguration();
      // Update the visibility conditions on the block.
      $condition_collection->addInstanceId($condition_id, $condition_configuration);
    }
  }

}
