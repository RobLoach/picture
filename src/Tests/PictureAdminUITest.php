<?php

/**
 * @file
 * Definition of Drupal\picture\Tests\PictureAdminUITest.
 */

namespace Drupal\picture\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Thoroughly test the administrative interface of the Picture module.
 *
 * @group picture
 */
class PictureAdminUITest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'responsive_image',
    'responsive_image_test_module',
    'picture'
  );

  /**
   * Drupal\simpletest\WebTestBase\setUp().
   */
  protected function setUp() {
    parent::setUp();

    // Create user.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer responsive images',
    ));

    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test picture administration functionality.
   */
  public function testPictureAdmin() {
    // We start without any default mappings.
    $this->drupalGet('admin/config/media/responsive-image-mapping');
    $this->assertText('There is no Responsive image mapping yet.');

    // Add a new responsive image mapping, our breakpoint set should be selected.
    $this->drupalGet('admin/config/media/responsive-image-mapping/add');
    $this->assertFieldByName('breakpointGroup', 'responsive_image_test_module');

    // Create a new group.
    $edit = array(
      'label' => 'Mapping One',
      'id' => 'mapping_one',
      'breakpointGroup' => 'responsive_image_test_module',
    );
    $this->drupalPostForm('admin/config/media/responsive-image-mapping/add', $edit, t('Save'));

    // Check if the new group is created.
    $this->assertResponse(200);
    $this->drupalGet('admin/config/media/responsive-image-mapping');
    $this->assertNoText('There is no Responsive image mapping yet.');
    $this->assertText('Mapping One');
    $this->assertText('mapping_one');

    // Edit the group.
    $this->drupalGet('admin/config/media/responsive-image-mapping/mapping_one');
    $this->assertFieldByName('label', 'Mapping One');
    $this->assertFieldByName('breakpointGroup', 'responsive_image_test_module');

    $cases = array(
      array('mobile', '1x'),
      array('mobile', '2x'),
      array('narrow', '1x'),
      array('narrow', '2x'),
      array('wide', '1x'),
      array('wide', '2x'),
    );

    foreach ($cases as $case) {
      // Check if the radio buttons are present.
      $this->assertFieldByName('keyed_mappings[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][image_mapping_type]', '');
      // Check if the image style dropdowns are present.
      $this->assertFieldByName('keyed_mappings[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][image_mapping_type]', '');
      // Check if the sizes textfields are present.
      $this->assertFieldByName('keyed_mappings[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][sizes]', '');
      // Check if the image styles checkboxes are present.
      foreach (array_keys(image_style_options(FALSE)) as $image_style_name) {
        $this->assertFieldByName('keyed_mappings[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][sizes_image_styles][' . $image_style_name . ']');
      }
    }

    // Save mappings for 1x variant only.
    $edit = array(
      'label' => 'Mapping One',
      'breakpointGroup' => 'responsive_image_test_module',
      'keyed_mappings[responsive_image_test_module.mobile][1x][image_mapping_type]' => 'image_style',
      'keyed_mappings[responsive_image_test_module.mobile][1x][image_style]' => 'thumbnail',
      'keyed_mappings[responsive_image_test_module.narrow][1x][image_mapping_type]' => 'sizes',
      'keyed_mappings[responsive_image_test_module.narrow][1x][sizes]' => '(min-width: 700px) 700px, 100vw',
      'keyed_mappings[responsive_image_test_module.narrow][1x][sizes_image_styles][large]' => 'large',
      'keyed_mappings[responsive_image_test_module.narrow][1x][sizes_image_styles][medium]' => 'medium',
      'keyed_mappings[responsive_image_test_module.wide][1x][image_mapping_type]' => 'image_style',
      'keyed_mappings[responsive_image_test_module.wide][1x][image_style]' => 'large',
    );
    $this->drupalPostForm('admin/config/media/responsive-image-mapping/mapping_one', $edit, t('Save'));
    $this->drupalGet('admin/config/media/responsive-image-mapping/mapping_one');

    // Check the mapping for multipliers 1x and 2x for the mobile breakpoint.
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.mobile][1x][image_style]', 'thumbnail');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.mobile][1x][image_mapping_type]', 'image_style');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.mobile][2x][image_style]', '');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.mobile][2x][image_mapping_type]', '_none');

    // Check the mapping for multipliers 1x and 2x for the narrow breakpoint.
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.narrow][1x][image_style]', '');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.narrow][1x][image_mapping_type]', 'sizes');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.narrow][1x][sizes]', '(min-width: 700px) 700px, 100vw');
    $this->assertFieldChecked('edit-keyed-mappings-responsive-image-test-modulenarrow-1x-sizes-image-styles-large');
    $this->assertFieldChecked('edit-keyed-mappings-responsive-image-test-modulenarrow-1x-sizes-image-styles-medium');
    $this->assertNoFieldChecked('edit-keyed-mappings-responsive-image-test-modulenarrow-1x-sizes-image-styles-thumbnail');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.narrow][2x][image_style]', '');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.narrow][2x][image_mapping_type]', '_none');

    // Check the mapping for multipliers 1x and 2x for the wide breakpoint.
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.wide][1x][image_style]', 'large');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.wide][1x][image_mapping_type]', 'image_style');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.wide][2x][image_style]', '');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.wide][2x][image_mapping_type]', '_none');

    // Delete the mapping.
    $this->drupalGet('admin/config/media/responsive-image-mapping/mapping_one/delete');
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->drupalGet('admin/config/media/responsive-image-mapping');
    $this->assertText('There is no Responsive image mapping yet.');
  }

}
