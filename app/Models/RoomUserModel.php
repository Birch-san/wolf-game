<?php
namespace App\Models;

use CodeIgniter\Model;

class RoomUserModel extends Model
{
  protected $table      = 'room_users';
  protected $primaryKey = 'id';

  protected $returnType = 'App\Entities\RoomUser';
  protected $useSoftDeletes = false;

  protected $allowedFields = ['id', 'room_id', 'user_id', 'joined', 'latest_poll'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
