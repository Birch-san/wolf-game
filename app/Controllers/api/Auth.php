<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Session\Session;
use Config\Database;
use Config\Services;
use Exception;
use IdGenerator;

class RegisterResponse {
  /** @var string */
  public $id;
  /** @var string */
  public $name;

  public function __construct(string $id, string $name)
  {
    $this->id = $id;
    $this->name = $name;
  }
}

class MeResponse {
  /** @var bool */
  public $registered;
  /** @var string|null */
  public $id;
  /** @var string|null */
  public $name;

  public function __construct(bool $registered, ?string $id, ?string $name)
  {
    $this->id = $id;
    $this->name = $name;
    $this->registered = $registered;
  }
}

class Auth extends BaseController
{
  use ResponseTrait;

  /** @var Session */
  protected $session;
  /** @var ConnectionInterface */
  protected $db;
  /** @var UserModel */
  protected $userModel;

  public function __construct()
  {
    $this->session =& Services::session();
    $this->db =& Database::connect();
    $this->userModel = new UserModel($db);
  }

  public function me()
  {
    $id = (string) $this->session->get('id');
    $name = (string) $this->session->get('name');
    return $this->respond(new MeResponse(!empty($id), $id, $name));
  }

	public function register()
  {
    if ($id = (string) !empty($this->session->get('id'))) {
      $name = (string) $this->session->get('name');
      return $this->respond(new RegisterResponse($id, $name));
    }
//    $query = $this->db->query(/** @lang MariaDB */ 'SELECT UUID() AS id');
//    $row = $query->getRow();
//    $id = $row->id;
    $id = IdGenerator::generateId();

    $name = 'lol';

    $user = new User();
    $user->id = $id;
    $user->name = $name;
    $this->userModel->insert($user);

    $this->session->set([
      'id' => $id,
      'name' => $name
    ]);
    return $this->respond(new RegisterResponse($id, $name));
  }

  public function logout()
  {
    $this->session->destroy();
    return $this->respond('Successfully logged out');
  }

	//--------------------------------------------------------------------

}
