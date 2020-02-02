<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\RoomModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;
use Config\Database;

class JoinRoomResponse {
  /** @var string */
  public $name;
  /** @var string */
  public $lastUpdated;
  /** @var array[][] */
  public $grid;

  /**
   * @param string $name
   * @param Time $lastUpdated
   * @param array[][] $grid
   */
  public function __construct(string $name, Time $lastUpdated, array $grid)
  {
    $this->name = $name;
    $this->lastUpdated = $lastUpdated->format(DATE_ISO8601);
    $this->grid = $grid;
  }
}

class Room extends BaseController
{
  use ResponseTrait;

  /** @var ConnectionInterface */
  protected $db;
  /** @var RoomModel */
  protected $roomModel;

  public function __construct()
  {
    $this->db =& Database::connect();
    $this->roomModel = new RoomModel($db);
  }

	public function join(string $name)
  {
    $this->db->transBegin();
    $this->db->transStrict(false);
    /** @var \App\Entities\Room|null $room */
    $room = $this->roomModel->find($name);
    if (is_null($room)) {
      $room = new \App\Entities\Room();
      $room->name = $name;
      $room->last_updated = new Time();
      $this->roomModel->insert($room);
    }
    $this->db->transComplete();
    return $this->respond(new JoinRoomResponse(
      $name,
      $room->last_updated,
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
