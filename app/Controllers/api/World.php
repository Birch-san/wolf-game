<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\ConnectionInterface;
use Config\Database;

class WolfView {
//  /** @var string */
//  public $id;
  /** @var string */
  public $name;
  public function __construct(
//    string $id,
    string $name
  ) {
//    $this->id = $id;
    $this->name = $name;
  }
}

class GetWorldResponse {
  /** @var WolfView[] */
  public $wolves;

  /** @param WolfView[] $wolves */
  public function __construct(array $wolves)
  {
    $this->wolves = $wolves;
  }
}

class World extends BaseController
{
  use ResponseTrait;

  /** @var ConnectionInterface */
  protected $db;
//  /** @var WolfModel */
//  protected $wolfModel;

  public function __construct()
  {
    $this->db =& Database::connect();
//    $this->wolfModel = new WolfModel($db);
  }

	public function index()
  {
    return $this->respond(new GetWorldResponse([
      'abc' => new WolfView(
        'Barry'
      )
    ]));
  }

	//--------------------------------------------------------------------

}
