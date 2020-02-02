<?php
namespace App\Models;

use CodeIgniter\Model;

class RoomModel extends Model
{
  protected $table      = 'rooms';
  protected $primaryKey = 'name';

  protected $returnType = 'App\Entities\Room';
  protected $useSoftDeletes = false;

  protected $allowedFields = ['name', 'last_updated'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
