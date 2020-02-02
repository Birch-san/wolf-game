<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Database;

class Position {
  /** @var int */
  public $x;
  /** @var int */
  public $y;

  public function __construct(int $x, int $y)
  {
    $this->x = $x;
    $this->y = $y;
  }
}

class PlayerView {
  /** @var string */
  public $name;
  /** @var Position */
  public $position;
  public function __construct(
    string $name,
    Position $position
  ) {
    $this->name = $name;
    $this->position = $position;
  }
}

class HunterView extends PlayerView {
  public function __construct(
    string $name,
    Position $position
  ) {
    parent::__construct($name, $position);
  }
}

class WolfView extends PlayerView {
  public function __construct(
    string $name,
    Position $position
  ) {
    parent::__construct($name, $position);
  }
}

class Score
{
  /** @var int */
  public $wolves;
  /** @var int */
  public $hunters;

  public function __construct(int $wolves, int $hunters)
  {
    $this->wolves = $wolves;
    $this->hunters = $hunters;
  }
}

class GetWorldResponse {
  /** @var WolfView[] */
  public $wolves;
  /** @var HunterView[] */
  public $hunters;
  /** @var Score */
  public $score;

  /**
   * @param WolfView[] $wolves
   * @param HunterView[] $hunters
   * @param Score $score
   */
  public function __construct(array $wolves, array $hunters, Score $score)
  {
    $this->wolves = $wolves;
    $this->hunters = $hunters;
    $this->score = $score;
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

  public function _remap(string $method, string ...$params) {
    if (method_exists($this, $method))
    {
      switch($method) {
        /** @noinspection PhpMissingBreakStatementInspection */
        case 'room':
          if (count($params) > 1) {
            switch($params[2]) {
              case 'update':
              return $this->updateWorld(...array_slice($params, 1));
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

	public function room(string $room)
  {
    return $this->respond(new GetWorldResponse([
      'abc' => new WolfView(
        'Woolf',
        new Position(1, 0)
      )
    ], [
      '123' => new HunterView(
        'Barry',
        new Position(2, 2)
      )
    ],
    new Score(
      0,
      0
    )));
  }

	//--------------------------------------------------------------------

}
