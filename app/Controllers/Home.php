<?php
namespace App\Controllers;

use CodeIgniter\Database\ConnectionInterface;
use Config\Database;
use App\Models\WolfModel;

class Home extends BaseController
{
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

	//--------------------------------------------------------------------

}
