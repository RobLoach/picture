<?php

/**
 * @file
 * Implement an image field, based on the file module's file field.
 */

/**
 * Prepares variables for image formatter templates.
 *
 * Default template: image-sizes.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - image_styles: An array of image styles.
 *   - fallback_image_style: The fallback image style.
 *   - sizes: The sizes attribute for viewport-based selection of images.
 *     - http://www.whatwg.org/specs/web-apps/current-work/multipage/embedded-content.html#introduction-3:viewport-based-selection-2
 *   - path: An optional array containing the link 'path' and link 'options'.
 */
function template_preprocess_image_sizes_formatter(&$variables) {
  $variables['image'] = array(
    '#theme' => 'image',
  );
  $variables['image']['#attributes'] = $variables['item_attributes'];

  $item = $variables['item'];

  // Do not output an empty 'title' attribute.
  if (drupal_strlen($item->title) != 0) {
    $variables['image']['#title'] = $item->title;
  }

  if (($entity = $item->entity) && empty($item->uri)) {
    $path = $entity->getFileUri();
  }
  else {
    $path = $item->uri;
  }
  $variables['image']['#uri'] = _responsive_image_image_style_url($variables['fallback_image_style'], $path);
  $variables['image']["#alt"] = $item->alt;
  $variables['image']['#sizes'] = $variables['sizes'];

  // The link path and link options are both optional, but for the options to be
  // processed, the link path must at least be an empty string.
  // @todo Add support for route names.
  $variables['url'] = NULL;
  if (isset($variables['path']['path'])) {
    $path = $variables['path']['path'];
    $options = isset($variables['path']['options']) ? $variables['path']['options'] : array();
    $variables['url'] = url($path, $options);
  }

  $srcset = array();

  foreach ($variables['image_styles'] as $image_style_name) {
    // Get the dimensions.
    $dimensions = responsive_image_get_image_dimensions(
      $image_style_name,
      array(
        'width' => $item->width,
        'height' => $item->height,
      )
    );

    // Add the image source with its width descriptor.
    // http://www.w3.org/html/wg/drafts/html/master/embedded-content.html#image-candidate-string
    $srcset[] = array(
      'uri' => _responsive_image_image_style_url($image_style_name, $path),
      'width' => $dimensions['width'] . 'w',
    );
  }
  $variables['image']['#srcset'] = $srcset;
}
