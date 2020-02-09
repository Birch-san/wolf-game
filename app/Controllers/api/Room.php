<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Entities\Entity;
use App\Entities\Hunter;
use App\Entities\Player;
use App\Entities\RoomUser;
use App\Entities\Wolf;
use App\Libraries\Position;
use App\Models\EntityModel;
use App\Models\HunterModel;
use App\Models\PlayerModel;
use App\Models\RoomModel;
use App\Models\RoomUserModel;
use App\Models\UserModel;
use App\Models\WolfModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Database\Query;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Session\Session;
use Config\Database;
use Config\Services;
use ErrorResponse;
use Generator;
use IdGenerator;
use ReflectionException;

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

class ScoreView {
  /** @var int */
  public $hunter_score;

  /** @var int */
  public $hunter_count;

  /** @var int */
  public $wolf_score;

  /** @var int */
  public $wolf_count;

  public function __construct()
  {
    $this->hunter_score
      = $this->hunter_count
      = $this->wolf_score
      = $this->wolf_count
      = 0;
  }
}

/** @noinspection PhpUnused */
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
    $this->session = Services::session();
    $this->userId = (string) $this->session->get('id');

    $this->db = Database::connect();
    $this->roomModel = new RoomModel($db);
    $this->userModel = new UserModel($db);
    $this->roomUserModel = new RoomUserModel($db);
    $this->entityModel = new EntityModel($db);
    $this->playerModel = new PlayerModel($db);
    $this->wolfModel = new WolfModel($db);
    $this->hunterModel = new HunterModel($db);
  }

  /** @noinspection PhpUnused */
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

  /**
   * @param string $name
   * @return mixed
   * @throws ReflectionException
   */
  public function join(string $name)
  {
    $this->db->transBegin();
    $now = new Time();
    /** @var \App\Entities\Room|null $room */
    $room = $this->roomModel->find($name);
    if (is_null($room)) {
      $room = new \App\Entities\Room();
      $room->name = $name;
      $room->last_updated = $now;
      $room->terrain = [
        [1,0,0,0,0,0,0,1],
        [1,0,1,0,0,1,0,1],
        [1,0,0,0,1,0,0,1],
        [1,0,0,0,1,0,0,1],
        [1,0,0,0,0,0,0,1],
        [1,0,1,1,0,0,0,1],
        [1,0,0,1,0,0,1,1],
        [1,0,0,0,0,0,0,1],
      ];
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

    $scoreQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
select
    SUM(IF(p.type = 'hunter', p.score, 0)) as hunter_score,
    SUM(cast(p.type = 'hunter' as unsigned integer)) as hunter_count,
    SUM(IF(p.type = 'wolf', p.score, 0)) as wolf_score,
    SUM(cast(p.type = 'wolf' as unsigned integer)) as wolf_count
from entities e
join players p
  on p.entity_id = e.id
where room_id = ?
  and e.type = 'player';
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $scoreResults = $scoreQuery->execute($room->name);
    /** @var ScoreView[]|null $scores */
    $scores = $scoreResults->getCustomResultObject(ScoreView::class);
    $score = $scores[0] ?? new ScoreView();
    $playerType = $score->hunter_score > $score->wolf_score
    || ($score->hunter_score === $score->wolf_score
      && $score->hunter_count > $score->wolf_count)
      ? 'wolf'
      : 'hunter';

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
      /**
       * @param int $rowStart inclusive
       * @param int $rowEnd exclusive
       * @param int[][] $terrain
       * @return Generator
       */
      function findFreeTiles(int $rowStart, int $rowEnd, array &$terrain) {
        for ($y = $rowStart; $y < $rowEnd; $y++) {
          $row =& $terrain[$y];
          for ($x = 0; $x < count($row); $x++) {
            $cell =& $row[$x];
            if ($cell === 0) {
              yield new Position($x, $y);
            }
          }
        }
      }
      $rowCount = count($room->terrain);
      /** @var Generator|null $freeTileGenerator */
      $freeTileGenerator = null;
      if ($playerType === 'wolf') {
        $freeTileGenerator = findFreeTiles($rowCount - 1, $rowCount, $room->terrain);
      } else if ($playerType === 'hunter') {
        $freeTileGenerator = findFreeTiles(0, 2, $room->terrain);
      }
      /** @var Position[] $validSpawnPoints */
      $validSpawnPoints = iterator_to_array($freeTileGenerator, false);
      $spawnPoint = $validSpawnPoints[array_rand((array) $validSpawnPoints)];
      $entity->pos_x = $spawnPoint->x;
      $entity->pos_y = $spawnPoint->y;
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
      $player->type = $playerType;
      $player->alive = true;
      $player->respawn_ticks = 0;
      $player->score = 0;
      $this->playerModel->insert($player);
    }

    if ($player->type === 'wolf') {
      /** @var Wolf|null $wolf */
      $wolf = $this->wolfModel
        ->where('entity_id', $entity->id)
        ->first();
      if (is_null($wolf)) {
        $wolf = new Wolf();
        $wolf->entity_id = $entity->id;
        $wolf->howling = false;
        $this->wolfModel->insert($wolf);
      }
    } else if ($player->type === 'hunter') {
      /** @var Hunter|null $hunter */
      $hunter = $this->hunterModel
        ->where('entity_id', $entity->id)
        ->first();
      if (is_null($hunter)) {
        $hunter = new Hunter();
        $hunter->entity_id = $entity->id;
        $hunter->reload_ticks = 0;
        $this->hunterModel->insert($hunter);
      }
    }

    $this->db->transComplete();
    return $this->respond(new JoinRoomResponse(
      $name,
      $room->last_updated,
      $room->terrain
    ));
  }

	//--------------------------------------------------------------------

}
