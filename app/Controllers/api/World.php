<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\ConnectionInterface;
use Config\Database;

class HunterView {
  /** @var string */
  public $name;
  public function __construct(
    string $name
  ) {
    $this->name = $name;
  }
}

class WolfView {
  /** @var string */
  public $name;
  public function __construct(
    string $name
  ) {
    $this->name = $name;
  }
}

class GetWorldResponse {
  /** @var WolfView[] */
  public $wolves;
  public $hunters;

  /**
   * @param WolfView[] $wolves
   * @param HunterView[] $hunters
   */
  public function __construct(array $wolves, array $hunters)
  {
    $this->wolves = $wolves;
    $this->hunters = $hunters;
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
        'Woolf'
      )
    ], [
      '123' => new HunterView(
        'Barry'
      )
    ]));
  }

	//--------------------------------------------------------------------

}
