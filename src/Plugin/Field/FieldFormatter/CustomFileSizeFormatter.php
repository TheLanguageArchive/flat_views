<?php

namespace Drupal\flat_views\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'custom_file_size' formatter.
 *
 * @FieldFormatter(
 *   id = "custom_file_size",
 *   label = @Translation("Custom file size"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class CustomFileSizeFormatter extends FormatterBase {

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $bytes = $item->value;
      $elements[$delta] = [
        '#markup' => $this->formatBytes($bytes),
      ];
    }
    return $elements;
  }

  protected function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = $bytes > 0 ? floor(log($bytes) / log(1024)) : 0;
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
  }
}
