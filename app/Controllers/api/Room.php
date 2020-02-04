<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Entities\RoomUser;
use App\Models\RoomModel;
use App\Models\RoomUserModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;
use CodeIgniter\Session\Session;
use Config\Database;
use Config\Services;
use Exception;
use IdGenerator;

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
   * @param int[][] $grid
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

  /** @var Session */
  protected $session;
  /** @var ConnectionInterface */
  protected $db;
  /** @var RoomModel */
  protected $roomModel;
  /** @var UserModel */
  protected $userModel;
  /** @var RoomUserModel */
  protected $roomUserModel;
  /** @var string */
  protected $userId;

  public function __construct()
  {
    $this->session =& Services::session();
    $this->userId = (string) $this->session->get('id');
    if (empty($this->userId)) {
      throw new Exception('Not logged in');
    }

    $this->db =& Database::connect();
    $this->roomModel = new RoomModel($db);
    $this->userModel = new UserModel($db);
    $this->roomUserModel = new RoomUserModel($db);
  }

	public function join(string $name)
  {
    $this->db->transBegin();
    $this->db->transStrict(false);
    $now = new Time();
    /** @var \App\Entities\Room|null $room */
    $room = $this->roomModel->find($name);
    if (is_null($room)) {
      $room = new \App\Entities\Room();
      $room->name = $name;
      $room->last_updated = $now;
      $this->roomModel->insert($room);
    }
    $userId = (string) $this->session->get('id');
    /** @var RoomUser|null $roomUser */
    $roomUser = $this->roomUserModel->where('room_id', $room->name)
      ->where('user_id', $userId)
      ->first();
    if (is_null($roomUser)) {
      $roomUser = new RoomUser();
      $roomUser->id = IdGenerator::generateId();
      $roomUser->room_id = $room->name;
      $roomUser->user_id = $userId;
      $roomUser->joined = $now;
      $roomUser->latest_poll = $now;
      $this->roomUserModel->insert($roomUser);
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
