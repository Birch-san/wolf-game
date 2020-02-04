<?php
namespace App\Models;

use CodeIgniter\Model;

class RoomUserEntityModel extends Model
{
  protected $table      = 'room_user_entities';
  protected $primaryKey = 'id';

  protected $returnType = 'App\Entities\RoomUserEntity';
  protected $useSoftDeletes = false;

  protected $allowedFields = ['id', 'room_user_id', 'type', 'pos_x', 'pos_y'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
