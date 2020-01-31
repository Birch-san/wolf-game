<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\ConnectionInterface;
use Config\Database;

class Grid extends BaseController
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

	public function index()
  {
    return $this->respond([[1,0],[0,1]]);
  }

	//--------------------------------------------------------------------

}
