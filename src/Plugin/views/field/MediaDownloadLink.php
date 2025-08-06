<?php

namespace Drupal\flat_views\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Provides a download link for media entities with "Original File" media use,
 * using the flysystem.serve route with scheme and filepath parameters.
 *
 * @ViewsField("flat_views_media_download_link")
 */
class MediaDownloadLink extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {

    $mid_field = 'field_media_of_node_field_data_mid';

    if (empty($values->$mid_field)) {
      return;
    }

    $mid = $values->$mid_field;
    $media = Media::load($mid);

    if (!$media || !$media->hasField('field_media_use')) {
      return;
    }

    // Check for "Original File" tag on media_use field.
    $terms = $media->get('field_media_use')->referencedEntities();
    $has_original_file = FALSE;
    foreach ($terms as $term) {
      if (strtolower(trim($term->label())) === 'original file') {
        $has_original_file = TRUE;
        break;
      }
    }

    if (!$has_original_file) {
      return;
    }

    // Look for a file in known media file fields. NOTE: adapt this list as new media types are added.
    $file_fields = [
      'field_media_document',
      'field_media_file',
      'field_media_audio_file',
      'field_media_video_file',
      'field_media_image',
    ];

    $file = NULL;
    foreach ($file_fields as $field_name) {
      if ($media->hasField($field_name) && !$media->get($field_name)->isEmpty()) {
        $file = $media->get($field_name)->entity;
        if ($file instanceof File) {
          break;
        }
      }
    }

    if (!$file) {
      return ['#markup' => 'No file found'];
    }

    // Check access for current user on the file entity.
    $access = $file->access('view', \Drupal::currentUser());
    if (!$access) {
      return ['#markup' => 'Access denied'];
    }

    // Extract relative filepath after scheme.
    $uri = $file->getFileUri();
    if (strpos($uri, 'fedora://') !== 0) {
      return ['#markup' => 'File URI does not use fedora scheme'];
    }

    $filepath = substr($uri, strlen('fedora://'));

    // Build URL with flysystem.serve route:
    $url = Url::fromRoute('flysystem.serve', [
      'scheme' => 'fedora',
      'filepath' => $filepath,
    ]);

    return Link::fromTextAndUrl($this->t('Download'), $url)->toRenderable();
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

}

