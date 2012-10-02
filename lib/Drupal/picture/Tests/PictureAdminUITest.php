<?php

/**
 * @file
 * Definition of Drupal\picture\Tests\PictureAdminUITest.
 */

namespace Drupal\picture\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\picture\PictureMapping;
use Drupal\breakpoint\BreakpointSet;
use Drupal\breakpoint\Breakpoint;

/**
 * Tests for breakpoint sets admin interface.
 */
class PictureAdminUITest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('picture');

  /**
   * Drupal\simpletest\WebTestBase\getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Picture administration functionality',
      'description' => 'Thoroughly test the administrative interface of the picture module.',
      'group' => 'Picture',
    );
  }

  /**
   * Drupal\simpletest\WebTestBase\setUp().
   */
  public function setUp() {
    parent::setUp();

    // Create user.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer pictures',
    ));

    $this->drupalLogin($this->admin_user);

    // Add breakpointset and breakpoints.
    $breakpointset = new BreakpointSet();
    $breakpointset->id = 'atestset';
    $breakpointset->label = 'A test set';
    $breakpointset->sourceType = Breakpoint::SOURCE_TYPE_CUSTOM;

    $breakpoints = array();
    $breakpoint_names = array('small', 'medium', 'large');
    for ($i = 0; $i < 3; $i++) {
      $breakpoint = new Breakpoint;
      $breakpoint->name = $breakpoint_names[$i];
      $width = ($i + 1) * 200;
      $breakpoint->mediaQuery = "(min-width: {$width}px)";
      $breakpoint->source = 'user';
      $breakpoint->sourceType = 'custom';
      $breakpoint->multipliers = array(
        '1.5x' => 0,
        '2x' => '2x',
      );
      $breakpoint->save();
      $breakpointset->breakpoints[$breakpoint->id()] = $breakpoint;
    }
    $breakpointset->save();

  }

  /**
   * Test picture administration functionality.
   */
  public function testPictureAdmin() {
    // We start without any default mappings.
    $this->drupalGet('admin/config/media/picturemapping');
    $this->assertText('There is no Picture mapping yet.');

    // Add a new picture mapping, our breakpoint set should be selected.
    $this->drupalGet('admin/config/media/picturemapping/add');
    $this->assertFieldByName('breakpointSet', 'atestset');

    // Create a new group.
    $edit = array(
      'label' => 'Mapping One',
      'id' => 'mapping_one',
      'breakpointSet' => 'atestset',
    );
    $this->drupalPost('admin/config/media/picturemapping/add', $edit, t('Save'));

    // Check if the new group is created.
    $this->assertResponse(200);
    $this->drupalGet('admin/config/media/picturemapping');
    $this->assertNoText('There is no Picture mapping yet.');
    $this->assertText('Mapping One');
    $this->assertText('mapping_one');

    // Edit the group.
    $this->drupalGet('admin/config/media/picturemapping/mapping_one/edit');
    $this->assertFieldByName('label', 'Mapping One');
    $this->assertFieldByName('breakpointSet', 'atestset');

    // Check if the dropdows are present for the mappings.
    $this->assertFieldByName('mappings[custom.user.small][1x]', '');
    $this->assertFieldByName('mappings[custom.user.small][2x]', '');
    $this->assertFieldByName('mappings[custom.user.medium][1x]', '');
    $this->assertFieldByName('mappings[custom.user.medium][2x]', '');
    $this->assertFieldByName('mappings[custom.user.large][1x]', '');
    $this->assertFieldByName('mappings[custom.user.large][2x]', '');

    // Save mappings for 1x variant only.
    $edit = array(
      'label' => 'Mapping One',
      'breakpointSet' => 'atestset',
      'mappings[custom.user.small][1x]' => 'thumbnail',
      'mappings[custom.user.medium][1x]' => 'medium',
      'mappings[custom.user.large][1x]' => 'large',
    );
    $this->drupalPost('admin/config/media/picturemapping/mapping_one/edit', $edit, t('Save'));
    $this->drupalGet('admin/config/media/picturemapping/mapping_one/edit');
    $this->assertFieldByName('mappings[custom.user.small][1x]', 'thumbnail');
    $this->assertFieldByName('mappings[custom.user.small][2x]', '');
    $this->assertFieldByName('mappings[custom.user.medium][1x]', 'medium');
    $this->assertFieldByName('mappings[custom.user.medium][2x]', '');
    $this->assertFieldByName('mappings[custom.user.large][1x]', 'large');
    $this->assertFieldByName('mappings[custom.user.large][2x]', '');

    // Delete the mapping.
    $this->drupalGet('admin/config/media/picturemapping/mapping_one/delete');
    $this->drupalPost(NULL, array(), t('Delete'));
    $this->drupalGet('admin/config/media/picturemapping');
    $this->assertText('There is no Picture mapping yet.');
  }

}
