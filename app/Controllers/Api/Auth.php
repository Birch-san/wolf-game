<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use apimatic\jsonmapper\JsonMapper;
use App\Controllers\BaseController;
use App\Entities\Entity;
use App\Entities\User;
use App\Libraries\ErrorCodes;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Database\Query;
use CodeIgniter\Session\Session;
use Config\Database;
use Config\Services;
use ErrorResponse;
use IdGenerator;
use ReflectionException;

class RegisterResponse {
  /** @var string */
  public $id;
  /** @var string */
  public $name;
  /** @var string */
  public $password;

  public function __construct(string $id, string $name, string $password)
  {
    $this->id = $id;
    $this->name = $name;
    $this->password = $password;
  }
}

class MeResponse {
  /** @var bool */
  public $loggedIn;
  /** @var string|null */
  public $id;
  /** @var string|null */
  public $name;

  public function __construct(bool $loggedIn, ?string $id, ?string $name)
  {
    $this->id = $id;
    $this->name = $name;
    $this->loggedIn = $loggedIn;
  }
}

class LoginRequest {
  /** @var string */
  public $userId;

  /** @var string */
  public $password;
}

class LoginResponse {
  /** @var boolean */
  public $success;

  public function __construct(bool $success)
  {
    $this->success = $success;
  }
}

/** @noinspection PhpUnused */
class Auth extends BaseController
{
  use ResponseTrait;

  /** @var Session */
  protected $session;
  /** @var ConnectionInterface */
  protected $db;
  /** @var JsonMapper */
  protected $mapper;
  /** @var UserModel */
  protected $userModel;

  public function __construct()
  {
    $this->session = Services::session();
    $this->db = Database::connect();
    $this->userModel = new UserModel($db);
    $this->mapper = new JsonMapper();
  }

  public function me()
  {
    $id = (string) $this->session->get('id');
    $name = (string) $this->session->get('name');
    return $this->respond(new MeResponse(!empty($id), $id, $name));
  }

  public function login()
  {
    $body = $this->request->getJSON();
    /** @var LoginRequest|null $loginRequest */
    $loginRequest = $this->mapper->mapClass($body, LoginRequest::class);
    $this->db->transBegin();
    /** @var User|null $user */
    $user = $this->userModel->find($loginRequest->userId);
    if (is_null($user) || !password_verify($loginRequest->password,  $user->password_hash)) {
      $this->db->transComplete();
      return $this->respond(new LoginResponse(false));
    }
    $touchUserQuery = $this->db->prepare(function($db) {
      $sql = <<<SQL
update users u
set u.last_seen = now(3)
where u.id = ?
SQL;
      return (new Query($db))->setQuery($sql);
    });
    $touchUserQuery->execute($loginRequest->userId);
    $this->session->set([
      'id' => $loginRequest->userId,
      'name' => $user->name
    ]);
    $this->db->transComplete();
    return $this->respond(new LoginResponse(true));
  }

  /**
   * @return mixed
   * @throws ReflectionException
   */
	public function register()
  {
    $this->db->transBegin();
    $id = IdGenerator::generateId();

//    $names = [
//      'Bigby',
//      'Amaterasu',
//      'Greycub',
//    ];
//    $name = $names[array_rand($names)];
    $idPrefix = substr($id, 0, 3);
    $name = "Player $idPrefix";

    $password = IdGenerator::generateId();
    $user = new User();
    $user->id = $id;
    $user->name = $name;
    $user->password_hash = password_hash($password,  PASSWORD_DEFAULT);
    $result = $this->userModel->insert($user);

    if ($result) {
      return $this->respond(new ErrorResponse(ErrorCodes::FAILED_QUERY, 'Failed to insert user.'), 500);
    }

    $this->session->set([
      'id' => $id,
      'name' => $name
    ]);
    $this->db->transComplete();
    return $this->respond(new RegisterResponse($id, $name, $password));
  }

  public function logout()
  {
    $this->session->destroy();
    return $this->respond('Successfully logged out');
  }

	//--------------------------------------------------------------------

}
