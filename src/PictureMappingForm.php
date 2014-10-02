<?php

/**
 * @file
 * Contains Drupal\picture\PictureMappingForm.
 */

namespace Drupal\picture;

use Drupal\responsive_image\ResponsiveImageMappingForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the responsive image edit/add forms.
 */
class PictureMappingForm extends ResponsiveImageMappingForm {

  /**
   * Overrides Drupal\responsive_image\ResponsiveImageForm::form().
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The array containing the complete form.
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $image_styles = image_style_options(FALSE);
    $image_styles[RESPONSIVE_IMAGE_EMPTY_IMAGE] = $this->t('- empty image -');

//    $form['#attached']['css'][drupal_get_path('module', 'picture') . '/css/picture.admin.css'] = array();
//    $form['#attached']['js'][drupal_get_path('module', 'picture') . '/js/picture.admin.js'] = array();
    $form['#attached']['library'][] = 'picture/drupal.picture';
    $breakpoints = $this->breakpointManager->getBreakpointsByGroup($this->entity->getBreakpointGroup());
    foreach ($breakpoints as $breakpoint_id => $breakpoint) {
      foreach ($breakpoint->getMultipliers() as $multiplier) {
        $label = $breakpoint->getLabel() . ', ' . $multiplier . ' <code class="media-query">' . $breakpoint->getMediaQuery() . '</code>';
        $form['keyed_mappings'][$breakpoint_id][$multiplier] = array(
          '#type' => 'details',
          '#title' => $label,
          '#attributes' => array(
            'class' => array('responsive-image-mapping-breakpoint'),
          ),
        );
        $mapping_definition = $this->entity->getMappingDefinition($breakpoint_id, $multiplier);

        // Use image style by default.
        $form['keyed_mappings'][$breakpoint_id][$multiplier]['image_style'] = array(
          '#type' => 'select',
          '#title' => $this->t('Image style'),
          '#options' => $image_styles,
          '#default_value' => isset($mapping_definition['image_style']) ? $mapping_definition['image_style'] : array(),
          '#description' => $this->t('Select an image style for this breakpoint.'),
          '#states' => array(
            'enabled' => array(
              ':input[name="keyed_mappings[' . $breakpoint_id . '][' . $multiplier . '][image_mapping_type]"]' => array('value' => 'image_style'),
            ),
          ),
        );

        // Show other options.
        $form['keyed_mappings'][$breakpoint_id][$multiplier]['image_mapping_type'] = array(
          '#type' => 'radios',
          '#options' => array(
            'image_style' => $this->t('Use image styles'),
            '_none' => $this->t('Do not use this breakpoint'),
            'sizes' => $this->t('Use the sizes attribute'),
          ),
          '#default_value' => isset($mapping_definition['image_mapping_type']) ? $mapping_definition['image_mapping_type'] : 'image_style',
        );

        // Simple sizes mode by default.
        $form['keyed_mappings'][$breakpoint_id][$multiplier]['size'] = array(
          '#type' => 'number',
          '#title' => $this->t('Relative width of the image'),
          '#default_value' => isset($mapping_definition['sizes']) ? $mapping_definition['sizes'] : '100',
          '#min' => 0,
          '#max' => 100,
          '#description' => $this->t('Enter the relative width of the image in regards to the browser width.'),
          '#states' => array(
            'visible' => array(
              ':input[name="keyed_mappings[' . $breakpoint_id . '][' . $multiplier . '][image_mapping_type]"]' => array('value' => 'sizes'),
              ':input[name="keyed_mappings[' . $breakpoint_id . '][' . $multiplier . '][sizes_advanced]"]' => array('checked' => FALSE),
            ),
          ),
        );
        $form['keyed_mappings'][$breakpoint_id][$multiplier]['preview_container'] = array(
          '#type' => 'container',
          '#states' => array(
            'visible' => array(
              ':input[name="keyed_mappings[' . $breakpoint_id . '][' . $multiplier . '][image_mapping_type]"]' => array('value' => 'sizes'),
              ':input[name="keyed_mappings[' . $breakpoint_id . '][' . $multiplier . '][sizes_advanced]"]' => array('checked' => FALSE),
            ),
          ),
          '#attributes' => array(
            'class' => array('responsive-image-mapping-preview'),
          ),
        );
        $form['keyed_mappings'][$breakpoint_id][$multiplier]['preview_container']['preview'] = array(
          '#theme' => 'image',
          '#uri' => \Drupal::config('image.settings')->get('preview_image'),
          '#alt' => t('Sample original image'),
          '#title' => '',
        );

        // Advanced sizes mode.
        $form['keyed_mappings'][$breakpoint_id][$multiplier]['sizes'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Sizes'),
          '#default_value' => isset($mapping_definition['sizes']) ? $mapping_definition['sizes'] : '',
          '#description' => $this->t('Enter the value for the sizes attribute (e.g. "(min-width:700px) 700px, 100vw").'),
          '#states' => array(
            'visible' => array(
              ':input[name="keyed_mappings[' . $breakpoint_id . '][' . $multiplier . '][image_mapping_type]"]' => array('value' => 'sizes'),
              ':input[name="keyed_mappings[' . $breakpoint_id . '][' . $multiplier . '][sizes_advanced]"]' => array('checked' => TRUE),
            ),
          ),
        );
        $form['keyed_mappings'][$breakpoint_id][$multiplier]['sizes_image_styles'] = array(
          '#title' => $this->t('Image styles'),
          '#type' => 'select',
          '#multiple' => TRUE,
          '#options' => array_diff_key($image_styles, array('' => '')),
          '#default_value' => isset($mapping_definition['sizes_image_styles']) ? $mapping_definition['sizes_image_styles'] : array(),
          '#states' => array(
            'visible' => array(
              ':input[name="keyed_mappings[' . $breakpoint_id . '][' . $multiplier . '][image_mapping_type]"]' => array('value' => 'sizes'),
            ),
          ),
        );
        $form['keyed_mappings'][$breakpoint_id][$multiplier]['sizes_advanced'] = array(
          '#title' => $this->t('Use the advanced mode'),
          '#type' => 'checkbox',
          '#default_value' => isset($mapping_definition['sizes_advanced']) ? $mapping_definition['sizes_advanced'] : FALSE,
          '#states' => array(
            'visible' => array(
              ':input[name="keyed_mappings[' . $breakpoint_id . '][' . $multiplier . '][image_mapping_type]"]' => array('value' => 'sizes'),
            ),
          ),
        );
      }
    }

    return $form;
  }

}
