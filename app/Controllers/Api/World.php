<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use apimatic\jsonmapper\JsonMapper;
use App\Controllers\BaseController;
use App\Entities\Entity;
use App\Entities\RoomUser;
use App\Libraries\ErrorCodes;
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
use IdGenerator;
use MessageResponse;
use ReflectionException;

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

  /** @var int|null */
  public $wolf_bite_ticks;

  /** @var int|null */
  public $wolf_petted_ticks;

  /** @var int|null */
  public $hunter_pet_ticks;

  /** @var int|null */
  public $hunter_bited_ticks;
}

class ActionDenormalizedView {
  /** @var string */
  public $user_id;

  /** @var Time */
  public $submitted_time_client;

  /** @var string */
  public $action_type;

  /** @var int|null */
  public $move_x;

  /** @var int|null */
  public $move_y;
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
  /** @var \CodeIgniter\I18n\Time
   * @noinspection PhpFullyQualifiedNameUsageInspection
   * @noinspection RedundantSuppression
   */
  public $time;
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

/**
 * @discriminator type
 * @discriminatorType bite
 */
class BiteActionRequest extends ActionRequest {
}

/**
 * @discriminator type
 * @discriminatorType pet
 */
class PetActionRequest extends ActionRequest {
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
    $this->mapper->arChildClasses[ActionRequest::class] = [
      MoveActionRequest::class,
      BiteActionRequest::class,
      PetActionRequest::class];
  }

  /** @noinspection PhpUnused */
  public function _remap(string $method, string ...$params) {
//    return $this->respond(new ErrorResponse(ErrorCodes::NOT_LOGGED_IN,'Not logged in'), 401);
    if (method_exists($this, $method))
    {
      if (empty($this->userId)) {
        return $this->respond(new ErrorResponse(ErrorCodes::NOT_LOGGED_IN,'Not logged in'), 401);
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
      return $this->respond(new ErrorResponse(ErrorCodes::CLIENT_ERROR, 'Failed to unmarshal action'), 400);
    }
    if ($actionRequest instanceof MoveActionRequest) {
      return $this->actMove($roomName, $actionRequest);
    } else if ($actionRequest instanceof BiteActionRequest) {
      return $this->actBite($roomName, $actionRequest);
    } else if ($actionRequest instanceof PetActionRequest) {
      return $this->actPet($roomName, $actionRequest);
    }

    return $this->respond(new ErrorResponse(ErrorCodes::CLIENT_ERROR,"Action '$actionRequest->type' not supported"), 400);
  }

  private function actBite(string $roomName, BiteActionRequest $request)
  {
    $actionId = IdGenerator::generateId();

    $actQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
insert into actions
    (id,
     room_id,
     user_id,
     type,
     submitted_time_client)
values
       (?, ?, ?, ?, ?)
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $actQuery->execute(
      $actionId,
      $roomName,
      $this->userId,
      'bite',
      $request->time->format('Y-m-d H:i:s.v')
    );

    return $this->respond(new MessageResponse("Successfully acted"));
  }

  private function actPet(string $roomName, PetActionRequest $request)
  {
    $actionId = IdGenerator::generateId();

    $actQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
insert into actions
    (id,
     room_id,
     user_id,
     type,
     submitted_time_client)
values
       (?, ?, ?, ?, ?)
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $actQuery->execute(
      $actionId,
      $roomName,
      $this->userId,
      'pet',
      $request->time->format('Y-m-d H:i:s.v')
    );

    return $this->respond(new MessageResponse("Successfully acted"));
  }

  private function actMove(string $roomName, MoveActionRequest $request)
  {
    $this->db->transBegin();
    $actionId = IdGenerator::generateId();

    $actQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
insert into actions
    (id,
     room_id,
     user_id,
     type,
     submitted_time_client)
values
       (?, ?, ?, ?, ?)
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $actQuery->execute(
      $actionId,
      $roomName,
      $this->userId,
      'move',
      $request->time->format('Y-m-d H:i:s.v')
      );

    $moveActQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
insert into move_actions
    (action_id,
     x,
     y)
values
       (?, ?, ?)
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $moveActQuery->execute(
      $actionId,
      $request->x,
      $request->y);

    $this->db->transComplete();
    return $this->respond(new MessageResponse("Successfully acted"));
  }

  /**
   * @param int[][] $terrain
   * @param EntityDenormalizedView $you
   * @param int $x
   * @param int $y
   * @throws ReflectionException
   */
  private function doMoveAct(
    array &$terrain,
    EntityDenormalizedView $you,
    int $x,
    int $y) {
    if ($you->player_type === 'wolf') {
      if ($you->wolf_petted_ticks > 0) {
        return;
      }
    } else if ($you->player_type === 'hunter') {
      if ($you->hunter_bited_ticks > 0) {
        return;
      }
    }
    $proposedX = $you->pos_x + $x;
    $proposedY = $you->pos_y + $y;
    if ($x > 1
      || $x < -1
      || $y > 1
      || $y < -1
      || ($x !== 0 && $y !== 0)
      || $proposedX > count($terrain[0]) - 1
      || $proposedX < 0
      || $proposedY > count($terrain) - 1
      || $proposedY < 0
      || $terrain[$proposedY][$proposedX] !== 0){
      return;
    }
    $this->entityModel->update($you->id, [
      'pos_x' => $proposedX,
      'pos_y' => $proposedY,
    ]);
  }

  /**
   * @param EntityDenormalizedView $you
   * @param EntityDenormalizedView[] $huntersWhereYouAt
   * @throws ReflectionException
   */
  private function doBiteAct(
    EntityDenormalizedView $you,
    array $huntersWhereYouAt
  ) {
    if ($you->player_type !== 'wolf') {
      return;
    }
    if (!count($huntersWhereYouAt)) {
      return;
    }
    $this->db->query(<<<SQL
update players p
set p.score = p.score + ?
where p.entity_id = ?
SQL
      , [count($huntersWhereYouAt), $you->id]);
    $this->wolfModel->update($you->id, [
      'bite_ticks' => 2,
    ]);
    $this->db->query(<<<SQL
update hunters h
set h.bited_ticks = ?
where h.entity_id IN ?
SQL
      , [2, array_column($huntersWhereYouAt, 'id')]);
  }

  /**
   * @param EntityDenormalizedView $you
   * @param EntityDenormalizedView[] $wolvesWhereYouAt
   * @throws ReflectionException
   */
  private function doPetAct(
    EntityDenormalizedView $you,
    array $wolvesWhereYouAt
  ) {
    if ($you->player_type !== 'hunter') {
      return;
    }
    if (!count($wolvesWhereYouAt)) {
      return;
    }
    $this->db->query(<<<SQL
update players p
set p.score = p.score + 1
where p.entity_id IN ?
SQL
      , [array_column($wolvesWhereYouAt, 'id') + [$you->id]]);
    $this->db->query(<<<SQL
update wolves w
set w.petted_ticks = ?
where w.entity_id IN ?
SQL
      , [2, array_column($wolvesWhereYouAt, 'id')]);
    $this->hunterModel->update($you->id, [
      'pet_ticks' => 2,
    ]);
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
      return $this->respond(new MessageResponse("Didn't acquire lock"));
    }

    $getRoomAgeQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
# 500,000 micros = 500 millis
select r.last_updated < DATE_SUB(NOW(3), INTERVAL r.update_freq_ms * 1000 MICROSECOND) AS room_old
from rooms r
where r.name = ?
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $getRoomAgeResults = $getRoomAgeQuery->execute($roomName);

    /** @var object $roomAge */
    $roomAge = $getRoomAgeResults->getFirstRow();
    if (!$roomAge->room_old) {
      return $this->respond(new MessageResponse("Room is not sufficiently old"));
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
delete ru, e, h, w, p, a, ma
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
left outer join actions a
  on a.room_id = ru.room_id
 and a.user_id = ru.user_id
left outer join move_actions ma
  on ma.action_id = a.id
where ru.latest_poll < DATE_SUB(NOW(), INTERVAL 15 SECOND)
  and ru.room_id = ?
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $garbageCollectQuery->execute($roomName);

    $getActionsQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
select q2.user_id,
       q2.submitted_time_client,
       q2.type as action_type,
       m.x as move_x,
       m.y as move_y
from (select id,
             user_id,
             type,
             submitted_time_client
from (select id,
             room_id,
             user_id,
             type,
             submitted_time_client
    from actions
    where room_id = ?
    order by room_id,
             user_id,
             type,
             submitted_time_client desc
    LIMIT 18446744073709551615
    ) q1
group by room_id,
         user_id,
         type desc) q2
left outer join move_actions m
  on q2.type = 'move'
 and m.action_id = q2.id
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $getActionsResults = $getActionsQuery->execute($roomName);
    /** @var ActionDenormalizedView[]|null $actions */
    $actions = $getActionsResults->getCustomResultObject(ActionDenormalizedView::class);

    /** @var EntityDenormalizedView[] $entities */
    $entities = $this->getEntitiesDenormalized($roomName) ?: [];

    $hunters = array_filter($entities, function(&$entity) {
      return $entity->player_type === 'hunter';
    });
    $wolves = array_filter($entities, function(&$entity) {
      return $entity->player_type === 'wolf';
    });
    $players = $hunters + $wolves;

    if (count($players)) {
      $this->db->query(<<<SQL
update players p
set
    p.respawn_ticks = greatest(0, p.respawn_ticks - 1)
where p.entity_id in ?
SQL
        , [array_column($players, 'id')]);

      $this->db->query(<<<SQL
update players p
set
    p.alive = 1
where p.entity_id in ?
  and p.alive = 0
  and p.respawn_ticks = 0
SQL
        , [array_column($players, 'id')]);
    }

    if (count($hunters)) {
      $this->db->query(<<<SQL
update hunters h
set
    h.bited_ticks = greatest(0, h.bited_ticks - 1),
    h.pet_ticks = greatest(0, h.pet_ticks - 1),
    h.reload_ticks = greatest(0, h.reload_ticks - 1)
where h.entity_id in ?
SQL
      , [array_column($hunters, 'id')]);
    }

    if (count($wolves)) {
      $this->db->query(<<<SQL
update wolves w
set
    w.bite_ticks = greatest(0, w.bite_ticks - 1),
    w.petted_ticks = greatest(0, w.petted_ticks - 1)
where w.entity_id in ?
SQL
        , [array_column($wolves, 'id')]);
    }

    /** @var \App\Entities\Room|null $room */
    $room = $this->roomModel->find($roomName);
    foreach($actions as $action) {

      /** @var EntityDenormalizedView|null $you */
      $you = null;
      foreach ($entities as $entity) {
        if ($entity->user_id === $action->user_id) {
          $you = $entity;
        }
      }
      if (is_null($you)) {
        continue;
      }
      if (!$you->player_alive) {
        continue;
      }
      /** @var EntityDenormalizedView[] $wolvesWhereYouAt */
      $wolvesWhereYouAt = [];
      /** @var EntityDenormalizedView[] $huntersWhereYouAt */
      $huntersWhereYouAt = [];
      foreach ($entities as $entity) {
        if ($entity->pos_x !== $you->pos_x
        || $entity->pos_y !== $you->pos_y) {
          continue;
        }
        if ($entity->player_type === 'wolf') {
          array_push($wolvesWhereYouAt, $entity);
        } else if ($entity->player_type === 'hunter') {
          array_push($huntersWhereYouAt, $entity);
        }
      }
      // TODO: probably fairer to process actions in order of type
      // TODO: use updateBatch()
      if ($action->action_type === 'move') {
        $this->doMoveAct(
          $room->terrain,
          $you,
          $action->move_x,
          $action->move_y);
      } else if ($action->action_type === 'bite') {
        $this->doBiteAct(
          $you,
          $huntersWhereYouAt);
      } else if ($action->action_type === 'pet') {
        $this->doPetAct(
          $you,
          $wolvesWhereYouAt);
      }
    }

    $garbageCollectActionsQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
delete a, ma
from actions a
left outer join move_actions ma
  on a.type = 'move'
 and a.id = ma.action_id
where a.room_id = ?
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $garbageCollectActionsQuery->execute($roomName);

    $releaseLockQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
SELECT RELEASE_LOCK(CONCAT('update_room_', ?))
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $releaseLockQuery->execute($roomName);

    $this->db->transComplete();
    return $this->respond(new MessageResponse("Successfully updated room '$roomName'"));
  }

  /**
   * @param string $roomName
   * @return EntityDenormalizedView[]|null
   */
  private function getEntitiesDenormalized(string $roomName): ?array {
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
    h1.pet_ticks as hunter_pet_ticks,
    h1.bited_ticks as hunter_bited_ticks,
    w1.howling as wolf_howling,
    w1.bite_ticks as wolf_bite_ticks,
    w1.petted_ticks as wolf_petted_ticks
from entities e
join users u
  on u.id = e.user_id
join players p
  on p.entity_id = e.id
left outer join
    (select
            h.entity_id,
            h.reload_ticks,
            h.pet_ticks,
            h.bited_ticks
    from hunters h) h1
    on p.type = 'hunter'
    and h1.entity_id = e.id
left outer join
     (select
             w.entity_id,
             w.howling,
             w.bite_ticks,
             w.petted_ticks
      from wolves w) w1
     on p.type = 'wolf'
     and w1.entity_id = e.id
where room_id = ?
  and e.type = 'player';
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $results = $query->execute($roomName);
    /** @var EntityDenormalizedView[]|null $entities */
    $entities = $results->getCustomResultObject(EntityDenormalizedView::class);
    return $entities;
  }

  /** @noinspection PhpUnused */
  public function room(string $roomName)
  {
//    return $this->respond(new ErrorResponse(ErrorCodes::CLIENT_ERROR, 'Suppressed'), 400);
    $this->db->transBegin();
    /** @var RoomUser|null $roomUser */
    $roomUser = $this->roomUserModel
      ->where('room_id', $roomName)
      ->where('user_id', $this->userId)
      ->first();
    if (is_null($roomUser)) {
      return $this->respond(new ErrorResponse(
        ErrorCodes::USER_NOT_IN_ROOM,
        "User <$this->userId> not in room"
      ), 400);
    }

    $entities = $this->getEntitiesDenormalized($roomName);
    $this->db->transComplete();
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
