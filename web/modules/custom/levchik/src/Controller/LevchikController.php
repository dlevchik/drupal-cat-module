<?php

namespace Drupal\levchik\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File as File;
use Drupal\Core\Url as Url;

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
        $file = File::load($fid);
        $uri = $file->getFileUri();
        $url = Url::fromUri(file_create_url($uri))->toString();
      }
      else {
        $url = "http://local.docksal/sites/default/files/levchik/underfined-cat.jpeg";
      }
      $item->picture_src = $url;
    }
    return $result;
  }

}
