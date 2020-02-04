<?php
namespace App\Models;

use CodeIgniter\Model;

class RoomUserPlayerModel extends Model
{
  protected $table      = 'room_user_players';
  protected $primaryKey = 'room_user_entity_id';

  protected $returnType = 'App\Entities\RoomUserPlayer';
  protected $useSoftDeletes = false;

  protected $allowedFields = ['room_user_entity_id', 'type', 'alive', 'respawn_ticks'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
