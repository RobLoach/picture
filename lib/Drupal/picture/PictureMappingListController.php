<?php

/**
 * @file
 * Definition of Drupal\picture\PictureListController.
 */

namespace Drupal\picture;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\picture\PictureMapping;

/**
 * Provides a listing of Pictures.
 */
class PictureMappingListController extends ConfigEntityListController {

  public function __construct($entity_type, $entity_info = FALSE) {
    parent::__construct($entity_type, $entity_info);
  }

  /**
   * Overrides Drupal\config\EntityListControllerBase::hookMenu();
   */
  public function hookMenu() {
    $path = $this->entityInfo['list path'];
    $items = parent::hookMenu();

    // Override the access callback.
    $items[$path]['title'] = 'Picture Mappings';
    $items[$path]['description'] = 'Manage list of pictures.';
    $items[$path]['access callback'] = 'user_access';
    $items[$path]['access arguments'] = array('administer pictures');

    return $items;
  }

}
