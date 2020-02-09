<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
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
use CodeIgniter\Session\Session;
use Config\Database;
use Config\Services;
use ErrorResponse;

class PlayerView {
  /** @var string */
  public $username;
  /** @var string */
  public $entityId;
  /** @var Position|null */
  public $position;
  /** @var int */
  public $score;

  public function __construct(
    string $entityId,
    string $username,
    ?Position $position,
    int $score
  ) {
    $this->entityId = $entityId;
    $this->username = $username;
    $this->position = $position;
    $this->score = $score;
  }
}

class HunterView {
  /** @var PlayerView */
  public $player;

  public function __construct(
    PlayerView $player
  ) {
    $this->player = $player;
  }
}

class WolfView {
  /** @var PlayerView */
  public $player;

  public function __construct(
    PlayerView $player
  ) {
    $this->player = $player;
  }
}

class GetWorldResponse {
  /** @var WolfView[] */
  public $wolves;
  /** @var HunterView[] */
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

class EntityView {
  /** @var string */
  public $id;

  /** @var string|null */
  public $user_id;

  /** @var int|null */
  public $pos_x;

  /** @var int|null */
  public $pos_y;

  /** @var string */
  public $user_name;

  /** @var string|null */
  public $player_type;

  /** @var int */
  public $player_score;

  /** @var boolean|null */
  public $player_alive;

  /** @var int|null */
  public $player_respawn_ticks;

  /** @var int|null */
  public $hunter_reload_ticks;

  /** @var boolean|null */
  public $wolf_howling;
}

/** @noinspection PhpUnused */
class World extends BaseController
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
  public function _remap(string $method, string ...$params) {
    if (method_exists($this, $method))
    {
      if (empty($this->userId)) {
        return $this->respond(new ErrorResponse('Not logged in'), 401);
      }
      switch($method) {
        /** @noinspection PhpMissingBreakStatementInspection */
        case 'room':
          if (count($params) > 1) {
            switch($params[1]) {
              case 'update':
              return $this->updateWorld($params[0]);
            }
          }
        default:
        return $this->$method(...$params);
      }
    }
    throw PageNotFoundException::forPageNotFound();
  }

  private function updateWorld(string $room)
  {
    return $this->respond($room);
  }

  /** @noinspection PhpUnused */
  public function room(string $roomName)
  {
    $query = $this->db->prepare(function($db) {
      $sql = <<<SQL
select
    e.id,
    e.user_id,
    e.pos_x,
    e.pos_y,
    u.name as user_name,
    p.alive as player_alive,
    p.score as player_score,
    p.respawn_ticks as player_respawn_ticks,
    p.type as player_type,
    h1.reload_ticks as hunter_reload_ticks,
    w1.howling as wolf_howling
from entities e
join users u
  on u.id = e.user_id
join players p
  on p.entity_id = e.id
left outer join
    (select
            h.entity_id,
            h.reload_ticks
    from hunters h) h1
    on p.type = 'hunter'
    and h1.entity_id = e.id
left outer join
     (select
             w.entity_id,
             w.howling
      from wolves w) w1
     on p.type = 'wolf'
     and w1.entity_id = e.id
where room_id = ?
  and e.type = 'player';
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $results = $query->execute($roomName);
    /** @var EntityView[]|null $entities */
    $entities = $results->getCustomResultObject(EntityView::class);
    $response = array_reduce(
      $entities,
      /**
       * @param GetWorldResponse $acc
       * @param EntityView $entity
       * @return GetWorldResponse
       */
      function(&$acc, &$entity) {
        $position = is_null($entity->pos_x)
        ? null
        : new Position(
          $entity->pos_x,
          $entity->pos_y);
        $playerView = new PlayerView(
          $entity->id,
          $entity->user_name,
          $position,
          $entity->player_score
        );
        switch($entity->player_type) {
          case 'wolf':
            array_push($acc->wolves, new WolfView($playerView));
            break;
          case 'hunter':
            array_push($acc->hunters, new HunterView($playerView));
            break;
        }
        return $acc;
      },
      new GetWorldResponse([], []));
    return $this->respond($response);
  }

	//--------------------------------------------------------------------

}
