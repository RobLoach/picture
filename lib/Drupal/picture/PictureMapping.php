<?php

/**
 * @file
 * Definition of Drupal\picture\PictureMapping.
 */

namespace Drupal\picture;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Picture entity.
 */
class PictureMapping extends ConfigEntityBase {

  /**
   * The Picture ID (machine name).
   *
   * @var string
   */
  public $id;

  /**
   * The Picture UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The Picture label.
   *
   * @var string
   */
  public $label;

  /**
   * The Picture mappings.
   *
   * @var array
   */
  public $mappings = array();

  /**
   * The Picture BreakpointGroup.
   *
   * @var BreakpointGroup
   */
  public $breakpointGroup = '';

  /**
   * Overrides Drupal\config\ConfigEntityBase::__construct().
   */
  public function __construct(array $values = array(), $entity_type = 'picture_mapping') {
    parent::__construct($values, $entity_type);
    $this->loadBreakpointGroup();
    $this->loadAllMappings();
  }

  /**
   * Overrides Drupal\Core\Entity::save().
   */
  public function save() {
    // Only save the keys, but return the full objects.
    if (isset($this->breakpointGroup) && is_object($this->breakpointGroup)) {
      $this->breakpointGroup = $this->breakpointGroup->id();
    }
    parent::save();
    $this->loadBreakpointGroup();
    $this->loadAllMappings();
  }

  /**
   * Implements EntityInterface::createDuplicate().
   */
  public function createDuplicate() {
    $duplicate = new PictureMapping();
    $duplicate->id = '';
    $duplicate->label = t('Clone of') . ' ' . $this->label();
    $duplicate->mappings = $this->mappings;
    return $duplicate;
  }

  /**
   * Load breakpointGroup.
   */
  protected function loadBreakpointGroup() {
    if ($this->breakpointGroup) {
      $breakpoint_group = breakpoint_group_load($this->breakpointGroup);
      $this->breakpointGroup = $breakpoint_group;
    }
  }

  /**
   * Load all mappings, remove non-existing ones.
   */
  protected function loadAllMappings() {
    $loaded_mappings = $this->mappings;
    $this->mappings = array();
    if ($this->breakpointGroup) {
      foreach ($this->breakpointGroup->breakpoints as $breakpoint_id => $breakpoint) {
        // Get the mapping for the default multiplier.
        $this->mappings[$breakpoint_id]['1x'] = '';
        if (isset($loaded_mappings[$breakpoint_id]['1x'])) {
          $this->mappings[$breakpoint_id]['1x'] = $loaded_mappings[$breakpoint_id]['1x'];
        }

        // Get the mapping for the other multipliers.
        if (isset($breakpoint->multipliers) && !empty($breakpoint->multipliers)) {
          foreach ($breakpoint->multipliers as $multiplier => $status) {
            if ($status) {
              $this->mappings[$breakpoint_id][$multiplier] = '';
              if (isset($loaded_mappings[$breakpoint_id][$multiplier])) {
                $this->mappings[$breakpoint_id][$multiplier] = $loaded_mappings[$breakpoint_id][$multiplier];
              }
            }
          }
        }
      }
    }
  }
}
