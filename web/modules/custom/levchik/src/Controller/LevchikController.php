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
   * Builds the response for cats page.
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
   * Builds the response table for administer cats page.
   */
  public function adminList() {
    $build = [
      'content' => [
        '#type' => 'item',
        '#markup' => $this->t('Hello! You can administer of your cats.'),
      ],
    ];
    return $build;
  }

  /**
   * Searches cats or one cat in db.
   *
   * @param string $id
   *   ID of the cat to search in db.
   *
   * @return array
   *   Cat's objects array(May be only one particular cat).
   */
  public static function getCats(string $id = NULL) {
    $database = \Drupal::database();
    $query = $database->select('levchik', 'lv');
    $query->fields('lv');
    if (!is_null($id)) {
      $query->condition('id', $id);
    }
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
