<?php

namespace Drupal\levchik\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for levchik routes.
 */
class LevchikController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {
    $cats = $this->getCats();

    $build = [
      'content' => [
        '#type' => 'item',
        '#markup' => $this->t('Hello! You can add here a photo of your cat.'),
      ],
      'form' => \Drupal::formBuilder()->getForm('\Drupal\levchik\Form\CatsForm'),
      'cats' => [
        '#theme' => 'levchik_theme_hook',
        '#cats' => $cats,
      ],
    ];

    return $build;
  }

  /**
   * Get's cats array from db.
   */
  public function getCats() {
    $database = \Drupal::database();
    $query = $database->select('levchik', 'lv');
    $query->fields('lv');
    $query->orderBy('created', 'DESC');

    $result = $query->execute()->fetchAll();
    foreach ($result as $item) {
      $fid = $item->picture_fid ? $item->picture_fid : 0;
      if (isset($fid) && $fid != 0) {
        $file = \Drupal\file\Entity\File::load($fid);
        $uri = $file->getFileUri();
        $url = \Drupal\Core\Url::fromUri(file_create_url($uri))->toString();
      } else {
        $url = "http://local.docksal/sites/default/files/levchik/underfined-cat.jpeg";
      }
      $item->picture_src = $url;
    }
    return $result;
  }

}
