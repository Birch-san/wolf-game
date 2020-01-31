<?php
namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\ConnectionInterface;
use Config\Database;
use App\Models\WolfModel;

class Home extends BaseController
{
  use ResponseTrait;

  /** @var ConnectionInterface */
  protected $db;
  /** @var WolfModel */
  protected $wolfModel;

  public function __construct()
  {
    $this->db =& Database::connect();
    $this->wolfModel = new WolfModel($db);
  }

	public function index()
	{
    $wolf = $this->wolfModel->first();
    return $wolf->name;
	}

  public function grid()
  {
    return $this->respond([[1,0],[0,1]]);
  }

	//--------------------------------------------------------------------

}
