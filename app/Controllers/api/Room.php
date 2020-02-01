<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\ConnectionInterface;

class JoinRoomResponse {
  /** @var string */
  public $name;
  /** @var array[][] */
  public $grid;

  /**
   * @param string $name
   * @param array[][] $grid
   */
  public function __construct(string $name, array $grid)
  {
    $this->name = $name;
    $this->grid = $grid;
  }
}

class Room extends BaseController
{
  use ResponseTrait;

  /** @var ConnectionInterface */
  protected $db;
//  /** @var WolfModel */
//  protected $wolfModel;

  public function __construct()
  {
//    $this->db =& Database::connect();
//    $this->wolfModel = new WolfModel($db);
  }

	public function join(string $name)
  {
    return $this->respond(new JoinRoomResponse(
      $name,
      [
      [1,0,0,0,0,0,0,1],
      [1,0,1,0,0,1,0,1],
      [1,0,0,0,1,0,0,1],
      [1,0,0,0,1,0,0,1],
      [1,0,0,0,0,0,0,1],
      [1,0,1,1,0,0,0,1],
      [1,0,0,1,0,0,1,1],
      [1,0,0,0,0,0,0,1],
    ]));
  }

	//--------------------------------------------------------------------

}
