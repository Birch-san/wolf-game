<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Entities\RoomUser;
use App\Entities\Entity;
use App\Entities\Player;
use App\Entities\Wolf;
use App\Models\RoomModel;
use App\Models\EntityModel;
use App\Models\HunterModel;
use App\Models\RoomUserModel;
use App\Models\PlayerModel;
use App\Models\WolfModel;
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
  /** @var EntityModel */
  protected $entityModel;
  /** @var PlayerModel */
  protected $playerModel;
  /** @var WolfModel */
  protected $wolfModel;
  /** @var HunterModel */
  protected $hunterModel;
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
    $this->entityModel = new EntityModel($db);
    $this->playerModel = new PlayerModel($db);
    $this->wolfModel = new WolfModel($db);
    $this->hunterModel = new HunterModel($db);
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
    $roomUser = $this->roomUserModel
      ->where('room_id', $room->name)
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

    /** @var Entity|null $entity */
    $entity = $this->entityModel
      ->where('room_id', $room->name)
      ->where('user_id', $userId)
      ->first();
    if (is_null($entity)) {
      $entity = new Entity();
      $entity->id = IdGenerator::generateId();
      $entity->room_id = $room->name;
      $entity->user_id = $userId;
      $entity->type = 'player';
      $this->entityModel->insert($entity);
    }

    /** @var Player|null $player */
    $player = $this->playerModel
      ->where('entity_id', $entity->id)
      ->first();
    if (is_null($player)) {
      $player = new Player();
      $player->id = IdGenerator::generateId();
      $player->entity_id = $entity->id;
      $player->type = 'wolf';
      $player->alive = true;
      $player->respawn_ticks = 0;
      $this->playerModel->insert($player);
    }

    /** @var Wolf|null $wolf */
    $wolf = $this->wolfModel
      ->where('entity_id', $entity->id)
      ->first();
    if (is_null($wolf)) {
      $wolf = new Wolf();
      $wolf->id = IdGenerator::generateId();
      $wolf->entity_id = $entity->id;
      $wolf->howling = true;
      $this->wolfModel->insert($wolf);
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
