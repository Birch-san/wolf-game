<?php
namespace App\Models;

use CodeIgniter\Model;

class RoomUserWolfModel extends Model
{
  protected $table      = 'room_user_wolves';
  protected $primaryKey = 'room_user_entity_id';

  protected $returnType = 'App\Entities\RoomUserWolf';
  protected $useSoftDeletes = false;

  protected $allowedFields = ['room_user_entity_id', 'howling'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
