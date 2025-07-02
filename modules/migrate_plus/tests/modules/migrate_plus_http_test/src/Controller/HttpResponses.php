<?php

namespace Drupal\migrate_plus_http_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * An example controller.
 */
class HttpResponses extends ControllerBase {

  /**
   * Returns a render-able array for a test page.
   */
  public function content() {

    $url = Url::fromRoute('migrate_plus_http_test.json_second', [], ['absolute' => TRUE]);

    $data = [
      "status" => "ok",
      'nextUrl' => $url->toString(),
      "data" => $this->generateRowsData(3),
    ];

    return new JsonResponse($data);
  }

  public function second(){
    $data = [
      "status" => "ok",
      'nextUrl' => NULL,
      "data" => $this->generateRowsData(3,4),
    ];

    return new JsonResponse($data);
  }

  public function third(Request $request){
    $page = $request->query->get('page') ?? 0;
    $first = $page*3 +1;

    $data = [
      "status" => "ok",
      'currentPage' => $page,
      'numPages' => 2,
      'nextPage' => $page+1,
      "data" => $this->generateRowsData(3,$first),
    ];

    if($page == 2){
      unset($data['nextPage']);
    }

    return new JsonResponse($data);
  }

  public function fourth( Request $request ) {
    $page = $request->query->get('cursor') ?? 0;
    $first = $page*3 +1;

    $data = [
      "status" => "ok",
      'nextPage' => $page+1,
      "data" => $this->generateRowsData(3,$first),
    ];

    if($page == 2){
      unset($data['nextPage']);
    }

    return new JsonResponse($data);
  }

  public function fifth( Request $request ) {
    $page = $request->query->get('page') ?? 0;
    $first = $page*3 +1;

    $data = [
      "status" => "ok",
      'numItems' => 3,
      "data" => $this->generateRowsData(3,$first),
    ];

    if($page == 2){
      $data['numItems'] = 0;
      $data["data"] = [];
    }

    if($page == 3){
      throw new NotFoundHttpException();
    }

    return new JsonResponse($data);
  }

  protected function generateRowsData(int $length, int $first = 1){
    $data = [];
    $last = $first + $length;
    for($i = $first; $i < $last; $i++){
      $data[] = [
        "id" => $i,
      ];
    }

    return $data;
  }

}
