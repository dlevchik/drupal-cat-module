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
    $renderer = \Drupal::service('renderer');
    foreach ($cats as &$cat) {
      $buttons = [
        '#theme' => 'levchik_cats_button',
        '#id' => $cat->id,
      ];
      $cat->cats_button = $renderer->render($buttons);
    }

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
   * Get name of main cats route.
   *
   * @return string
   *   name of main cats route
   */
  public static function getRouteName() {
    return 'levchik.сats';
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
        $url = "/modules/custom/levchik/img/underfined-cat.jpeg";
      }
      $item->picture_src = $url;
    }
    return $result;
  }

  /**
   * Saves cat data to db.
   *
   * @param object $cat
   *   Object with cat data.
   */
  public static function saveCat(\stdClass $cat) {
    $connection = \Drupal::service('database');
    $file_fid = $cat->picture_fid;
    if ($file_fid) {
      LevchikController::fileSavePermanent($file_fid);
    }
    $connection->insert('levchik')
      ->fields(['name', 'created', 'email', 'picture_fid'])
      ->values([
        'name' => $cat->name,
        'created' => \Drupal::time()->getRequestTime(),
        'email' => $cat->email,
        'picture_fid' => $file_fid ? $file_fid : 0,
      ])
      ->execute();
  }

  /**
   * Updates cat data from db.
   *
   * @param object $cat
   *   Object with cat data.
   */
  public static function editCat(\stdClass $cat) {
    $connection = \Drupal::service('database');
    $file_fid = $cat->picture_fid;
    if ($file_fid) {
      LevchikController::fileSavePermanent($file_fid);
    }
    $connection->update('levchik')
      ->condition('id', $cat->id)
      ->fields([
        'name' => $cat->name,
        'email' => $cat->email,
        'picture_fid' => $file_fid ? $file_fid : 0,
      ])
      ->execute();
  }

  /**
   * Deletes cat data from db.
   *
   * @param array $id
   *   Array with cats id's to delete.
   */
  public static function deleteCat(array $id = []) {
    if (empty($id)) {
      return;
    }
    $connection = \Drupal::service('database');
    $query = $connection->select('levchik', 'lv');
    $query->condition('id', $id, 'IN');
    $query->fields('lv', ['picture_fid']);
    $result = $query->execute()->fetchAll();
    foreach ($result as $item) {
      $fid = $item->picture_fid;
      if ($fid != "0") {
        $file = File::load($fid);
        $file->delete();
      }
    }
    $cat_deleted = $connection->delete('levchik')
      ->condition('id', $id, 'IN')
      ->execute();
  }

  /**
   * Function to make fresh downloaded file permanent to drupal.
   *
   * @param int $fid
   *   File id to make permanent.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function fileSavePermanent(int $fid) {
    $file = File::load($fid);
    $file->setPermanent();
    $file->save();
  }

}
