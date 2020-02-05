<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Entities\RoomUser;
use App\Entities\RoomUserEntity;
use App\Entities\RoomUserPlayer;
use App\Entities\RoomUserWolf;
use App\Models\RoomModel;
use App\Models\RoomUserEntityModel;
use App\Models\RoomUserHunterModel;
use App\Models\RoomUserModel;
use App\Models\RoomUserPlayerModel;
use App\Models\RoomUserWolfModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Session\Session;
use Config\Database;
use Config\Services;
use ErrorResponse;
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
  /** @var RoomUserEntityModel */
  protected $roomUserEntityModel;
  /** @var RoomUserPlayerModel */
  protected $roomUserPlayerModel;
  /** @var RoomUserWolfModel */
  protected $roomUserWolfModel;
  /** @var RoomUserHunterModel */
  protected $roomUserHunterModel;
  /** @var string */
  protected $userId;

  public function __construct()
  {
    $this->session =& Services::session();
    $this->userId = (string) $this->session->get('id');

    $this->db =& Database::connect();
    $this->roomModel = new RoomModel($db);
    $this->userModel = new UserModel($db);
    $this->roomUserModel = new RoomUserModel($db);
    $this->roomUserEntityModel = new RoomUserEntityModel($db);
    $this->roomUserPlayerModel = new RoomUserPlayerModel($db);
    $this->roomUserWolfModel = new RoomUserWolfModel($db);
    $this->roomUserHunterModel = new RoomUserHunterModel($db);
  }

  public function _remap($method, ...$params)
  {
    if (method_exists($this, $method))
    {
      if (empty($this->userId)) {
        return $this->respond(new ErrorResponse('Not logged in'), 401);
      }
      return $this->$method(...$params);
    }
    throw PageNotFoundException::forPageNotFound();
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

    /** @var RoomUserEntity|null $roomUserEntity */
    $roomUserEntity = $this->roomUserEntityModel->where('room_user_id', $roomUser->id)
      ->first();
    if (is_null($roomUserEntity)) {
      $roomUserEntity = new RoomUserEntity();
      $roomUserEntity->id = IdGenerator::generateId();
      $roomUserEntity->room_user_id = $roomUser->id;
      $roomUserEntity->type = 'player';
      $this->roomUserEntityModel->insert($roomUserEntity);
    }

    /** @var RoomUserPlayer|null $roomUserPlayer */
    $roomUserPlayer = $this->roomUserPlayerModel->where('room_user_entity_id', $roomUserEntity->id)
      ->first();
    if (is_null($roomUserPlayer)) {
      $roomUserPlayer = new RoomUserPlayer();
      $roomUserPlayer->id = IdGenerator::generateId();
      $roomUserPlayer->room_user_entity_id = $roomUserEntity->id;
      $roomUserPlayer->type = 'wolf';
      $roomUserPlayer->alive = true;
      $roomUserPlayer->respawn_ticks = 0;
      $this->roomUserPlayerModel->insert($roomUserPlayer);
    }

    /** @var RoomUserWolf|null $roomUserWolf */
    $roomUserWolf = $this->roomUserWolfModel->where('room_user_entity_id', $roomUserPlayer->id)
      ->first();
    if (is_null($roomUserWolf)) {
      $roomUserWolf = new RoomUserWolf();
      $roomUserWolf->id = IdGenerator::generateId();
      $roomUserWolf->room_user_entity_id = $roomUserPlayer->id;
      $roomUserWolf->howling = true;
      $this->roomUserWolfModel->insert($roomUserWolf);
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
