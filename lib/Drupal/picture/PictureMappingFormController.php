<?php

/**
 * @file
 * Definition of Drupal\picture\PictureFormController.
 */

namespace Drupal\picture;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;
use Drupal\picture\PictureMapping;

/**
 * Form controller for the picture edit/add forms.
 */
class PictureMappingFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param \Drupal\picture\PictureMapping $picture_mapping
   *   The entity being edited.
   *
   * @return array
   *   The array containing the complete form.
   */
  public function form(array $form, array &$form_state, EntityInterface $picture_mapping) {
    // Check if we need to duplicate the picture.
    if ($this->operation == 'duplicate') {
      $picture_mapping = $picture_mapping->createDuplicate();
      $this->setEntity($picture_mapping, $form_state);
    }
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $picture_mapping->label(),
      '#description' => t("Example: 'Main content' or 'Sidebar'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $picture_mapping->id(),
      '#machine_name' => array(
        'exists' => 'picture_mapping_load',
        'source' => array('label'),
      ),
      '#disabled' => (bool) $picture_mapping->id() && $this->operation != 'duplicate',
    );
    $form['breakpointSet'] = array(
      '#type' => 'select',
      '#title' => t('Breakpoint Set'),
      '#default_value' => !empty($picture_mapping->breakpointSet) ? $picture_mapping->breakpointSet->id() : '',
      '#options' => breakpoints_breakpointset_select_options(),
      '#required' => TRUE,
    );

    $image_styles = image_style_options(TRUE);
    foreach ($picture_mapping->mappings as $breakpoint_id => $mapping) {
      foreach ($mapping as $multiplier => $image_style) {
        $label = $multiplier . ' ' . $picture_mapping->breakpointSet->breakpoints[$breakpoint_id]->name . ' [' . $picture_mapping->breakpointSet->breakpoints[$breakpoint_id]->mediaQuery . ']';
        $form['mappings'][$breakpoint_id][$multiplier] = array(
          '#type' => 'select',
          '#title' => check_plain($label),
          '#options' => $image_styles,
          '#default_value' => $image_style,
        );
      }
    }

    $form['#tree'] = TRUE;

    return parent::form($form, $form_state, $picture_mapping);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    // Only includes a Save action for the entity, no direct Delete button.
    return array(
      'submit' => array(
        '#value' => t('Save'),
        '#validate' => array(
          array($this, 'validate'),
        ),
        '#submit' => array(
          array($this, 'submit'),
          array($this, 'save'),
        ),
      ),
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $picture_mapping = $this->getEntity($form_state);
    $picture_mapping->save();

    watchdog('picture', 'Picture mapping @label saved.', array('@label' => $picture_mapping->label()), WATCHDOG_NOTICE);
    drupal_set_message(t('Picture mapping %label saved.', array('%label' => $picture_mapping->label())));

    $form_state['redirect'] = 'admin/config/media/picturemapping';
  }

}
