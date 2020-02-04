<?php
namespace App\Models;

use CodeIgniter\Model;

class RoomUserHunterModel extends Model
{
  protected $table      = 'room_user_hunters';
  protected $primaryKey = 'room_user_player_id';

  protected $returnType = 'App\Entities\RoomUserHunter';
  protected $useSoftDeletes = false;

  protected $allowedFields = ['room_user_player_id', 'reload_ticks'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
