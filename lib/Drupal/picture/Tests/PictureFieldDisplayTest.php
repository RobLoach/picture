<?php

/**
 * @file
 * Definition of Drupal\picture\Tests\PictureFieldDisplayTest.
 */

namespace Drupal\picture\Tests;

use Drupal\picture\PictureMapping;
use Drupal\breakpoint\BreakpointSet;
use Drupal\breakpoint\Breakpoint;
use Drupal\image\Tests\ImageFieldTestBase;

/**
 * Test class to check that formatters and display settings are working.
 */
class PictureFieldDisplayTest extends ImageFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_ui', 'picture');

  /**
   * Drupal\simpletest\WebTestBase\getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Picture field display tests',
      'description' => 'Test picture display formatter.',
      'group' => 'Picture',
    );
  }

  /**
   * Drupal\simpletest\WebTestBase\setUp().
   */
  public function setUp() {
    parent::setUp();

    // Create user.
    $this->admin_user = $this->drupalCreateUser(array('administer pictures', 'access content', 'access administration pages', 'administer site configuration', 'administer content types', 'administer nodes', 'create article content', 'edit any article content', 'delete any article content', 'administer image styles'));
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

    // Add picture mapping.
    $picture_mapping = new PictureMapping();
    $picture_mapping->id = 'mapping_one';
    $picture_mapping->label = 'Mapping One';
    $picture_mapping->breakpointSet = 'atestset';
    $picture_mapping->save();
    $picture_mapping->mappings['custom.user.small']['1x'] = 'thumbnail';
    $picture_mapping->mappings['custom.user.medium']['1x'] = 'medium';
    $picture_mapping->mappings['custom.user.large']['1x'] = 'large';
    $picture_mapping->save();
  }

  /**
   * Test picture formatters on node display for public files.
   */
  function testPictureFieldFormattersPublic() {
    $this->_testPictureFieldFormatters('public');
  }

  /**
   * Test picture formatters on node display for private files.
   */
  private function xxtestPictureFieldFormattersPrivate() {
    // Remove access content permission from anonymous users.
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array('access content' => FALSE));
    $this->_testPictureFieldFormatters('private');
  }

  /**
   * Test picture formatters on node display.
   */
  function _testPictureFieldFormatters($scheme) {
    $field_name = strtolower($this->randomName());
    $this->createImageField($field_name, 'article', array('uri_scheme' => $scheme));
    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article');
    $node = node_load($nid, TRUE);

    // Test that the default formatter is being used.
    $image_uri = file_load($node->{$field_name}[LANGUAGE_NOT_SPECIFIED][0]['fid'])->uri;
    $image_info = array(
      'uri' => $image_uri,
      'width' => 40,
      'height' => 20,
    );
    $default_output = theme('image', $image_info);
    $this->assertRaw($default_output, 'Default formatter displaying correctly on full node view.');

    // Test the image linked to file formatter.
    $instance = field_info_instance('node', $field_name, 'article');
    $instance['display']['default']['type'] = 'picture';
    $instance['display']['default']['settings']['image_link'] = 'file';
    field_update_instance($instance);
    $default_output = l(theme('image', $image_info), file_create_url($image_uri), array('html' => TRUE));
    $this->drupalGet('node/' . $nid);
    $this->assertRaw($default_output, 'Image linked to file formatter displaying correctly on full node view.');
    // Verify that the image can be downloaded.
    $this->assertEqual(file_get_contents($test_image->uri), $this->drupalGet(file_create_url($image_uri)), 'File was downloaded successfully.');
    if ($scheme == 'private') {
      // Only verify HTTP headers when using private scheme and the headers are
      // sent by Drupal.
      $this->assertEqual($this->drupalGetHeader('Content-Type'), 'image/png', 'Content-Type header was sent.');
      $this->assertEqual($this->drupalGetHeader('Content-Disposition'), 'inline; filename="' . $test_image->filename . '"', 'Content-Disposition header was sent.');
      $this->assertTrue(strstr($this->drupalGetHeader('Cache-Control'),'private') !== FALSE, 'Cache-Control header was sent.');

      // Log out and try to access the file.
      $this->drupalLogout();
      $this->drupalGet(file_create_url($image_uri));
      $this->assertResponse('403', 'Access denied to original image as anonymous user.');

      // Log in again.
      $this->drupalLogin($this->admin_user);
    }

    // Test the image linked to content formatter.
    $instance['display']['default']['settings']['image_link'] = 'content';
    field_update_instance($instance);
    $default_output = l(theme('image', $image_info), 'node/' . $nid, array('html' => TRUE, 'attributes' => array('class' => 'active')));
    $this->drupalGet('node/' . $nid);
    $this->assertRaw($default_output, 'Image linked to content formatter displaying correctly on full node view.');

    // Test the image style 'thumbnail' formatter.
    $instance['display']['default']['settings']['image_link'] = '';
    $instance['display']['default']['settings']['fallback_image_style'] = 'thumbnail';
    field_update_instance($instance);
    // Ensure the derivative image is generated so we do not have to deal with
    // image style callback paths.
    $this->drupalGet(image_style_url('thumbnail', $image_uri));
    $image_info['uri'] = $image_uri;
    $image_info['width'] = 100;
    $image_info['height'] = 50;
    $image_info['style_name'] = 'thumbnail';
    $default_output = theme('image_style', $image_info);
    $this->drupalGet('node/' . $nid);
    $this->assertRaw($default_output, 'Image style thumbnail formatter displaying correctly on full node view.');

    if ($scheme == 'private') {
      // Log out and try to access the file.
      $this->drupalLogout();
      $this->drupalGet(image_style_url('thumbnail', $image_uri));
      $this->assertResponse('403', 'Access denied to image style thumbnail as anonymous user.');
    }
  }

}
