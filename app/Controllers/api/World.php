<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use apimatic\jsonmapper\JsonMapper;
use App\Controllers\BaseController;
use App\Entities\RoomUser;
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
  /** @var int */
  public $score;

  public function __construct(
    int $score
  ) {
    $this->score = $score;
  }
}

class UserView {
  /** @var string */
  public $id;

  /** @var string */
  public $name;

  public function __construct(
    string $id,
    string $name
  ) {
    $this->id = $id;
    $this->name = $name;
  }
}

class EntityView {
  /** @var string */
  public $id;

  /** @var Position|null */
  public $position;

  /** @var UserView|null */
  public $user;

  public function __construct(
    string $id,
    ?Position $position,
    ?UserView $user
  ) {
    $this->id = $id;
    $this->position = $position;
    $this->user = $user;
  }
}

class HunterView {
  /** @var EntityView */
  public $entity;

  /** @var PlayerView */
  public $player;

  public function __construct(
    EntityView $entity,
    PlayerView $player
  ) {
    $this->entity = $entity;
    $this->player = $player;
  }
}

class WolfView {
  /** @var EntityView */
  public $entity;

  /** @var PlayerView */
  public $player;

  public function __construct(
    EntityView $entity,
    PlayerView $player
  ) {
    $this->entity = $entity;
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

class EntityDenormalizedView {
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

class IdleUserView {
  /** @var string */
  public $user_id;
}

/**
 * @discriminator type
 */
class ActionRequest {
  /** @var string */
  public $type;
}

/**
 * @discriminator type
 * @discriminatorType move
 */
class MoveActionRequest extends ActionRequest {
  /** @var int */
  public $x;
  public $y;
}

/** @noinspection PhpUnused */
class World extends BaseController
{
  use ResponseTrait;

  /** @var Session */
  protected $session;
  /** @var ConnectionInterface */
  protected $db;
  /** @var JsonMapper */
  protected $mapper;
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

    $this->mapper = new JsonMapper();
    $this->mapper->arChildClasses[ActionRequest::class] = [MoveActionRequest::class];
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
              case 'act':
              return $this->act($params[0]);
            }
          }
        default:
        return $this->$method(...$params);
      }
    }
    throw PageNotFoundException::forPageNotFound();
  }

  private function act(string $roomName)
  {
    $body = $this->request->getJSON();
    /** @var ActionRequest|null $actionRequest */
    $actionRequest = $this->mapper->mapClass($body, ActionRequest::class);
    if (is_null($actionRequest)) {
      return $this->respond(new ErrorResponse('Failed to unmarshal action'), 400);
    }
    if ($actionRequest instanceof MoveActionRequest) {
      return $this->actMove($roomName, $actionRequest);
    }

    return $this->respond(new ErrorResponse("Action '$actionRequest->type' not supported"), 400);
  }

  private function actMove(string $roomName, MoveActionRequest $request)
  {
    return $this->respond($request);
  }

  private function updateWorld(string $roomName)
  {
    $touchRoomUserQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
update room_users ru
set ru.latest_poll = now(3)
where room_id = ?
  and user_id = ?
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $touchRoomUserQuery->execute($roomName, $this->userId);
    $this->db->transBegin();
//    $getIdleUsersQuery = $this->db->query(<<<SQL
//select user_id
//from room_users ru
//where ru.latest_poll < DATE_SUB(NOW(), INTERVAL 15 SECOND);
//SQL
//    );
//    /** @var string[] $userIds */
//    $userIds = array_column($getIdleUsersQuery->getResultArray(), 'user_id');
    $getLockQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
SELECT GET_LOCK(CONCAT('update_room_', ?), 1) AS lock_status
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $getLockResults = $getLockQuery->execute($roomName);

    /** @var object $lock */
    $lock = $getLockResults->getFirstRow();
    if (!$lock->lock_status) {
      return $this->respond("Didn't acquire lock");
    }

    $getRoomAgeQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
# 500,000 micros = 500 millis
select r.last_updated < DATE_SUB(NOW(3), INTERVAL 500000 MICROSECOND) AS room_old
from rooms r
where r.name = ?
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $getRoomAgeResults = $getRoomAgeQuery->execute($roomName);

    /** @var object $roomAge */
    $roomAge = $getRoomAgeResults->getFirstRow();
    if (!$roomAge->room_old) {
      return $this->respond("Room is not sufficiently old");
    }

    $touchRoomQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
update rooms r
set r.last_updated = now(3)
where r.name = ?
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $touchRoomQuery->execute($roomName);

    $garbageCollectQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
delete ru, e, h, w, p
from room_users ru
left outer join entities e
  on e.room_id = ru.room_id
 and e.user_id = ru.user_id
left outer join hunters h
  on h.entity_id = e.id
left outer join wolves w
  on w.entity_id = e.id
left outer join players p
  on p.entity_id = e.id
where ru.latest_poll < DATE_SUB(NOW(), INTERVAL 15 SECOND)
  and ru.room_id = ?
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $garbageCollectQuery->execute($roomName);

    $releaseLockQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
SELECT RELEASE_LOCK(CONCAT('update_room_', ?))
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $releaseLockQuery->execute($roomName);

    $this->db->transComplete();
    return $this->respond("Successfully updated room '$roomName'");
  }

  /** @noinspection PhpUnused */
  public function room(string $roomName)
  {
    $this->db->transBegin();
    /** @var RoomUser|null $roomUser */
    $roomUser = $this->roomUserModel
      ->where('room_id', $roomName)
      ->where('user_id', $this->userId)
      ->first();
    if (is_null($roomUser)) {
      return $this->respond([
        'error' => 'USER_NOT_IN_ROOM'
      ], 400);
    }

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
    $this->db->transComplete();
    /** @var EntityDenormalizedView[]|null $entities */
    $entities = $results->getCustomResultObject(EntityDenormalizedView::class);
    $response = array_reduce(
      $entities,
      /**
       * @param GetWorldResponse $acc
       * @param EntityDenormalizedView $entity
       * @return GetWorldResponse
       */
      function(&$acc, &$entity) {
        $position = is_null($entity->pos_x)
        ? null
        : new Position(
          $entity->pos_x,
          $entity->pos_y);
        $entityView = new EntityView(
          $entity->id,
          $position,
          new UserView(
            $entity->user_id,
            $entity->user_name
          )
        );
        $playerView = new PlayerView(
          $entity->player_score
        );
        switch($entity->player_type) {
          case 'wolf':
            array_push($acc->wolves, new WolfView($entityView, $playerView));
            break;
          case 'hunter':
            array_push($acc->hunters, new HunterView($entityView, $playerView));
            break;
        }
        return $acc;
      },
      new GetWorldResponse([], []));
    return $this->respond($response);
  }

	//--------------------------------------------------------------------

}
