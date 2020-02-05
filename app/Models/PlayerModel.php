<?php
namespace App\Models;

use CodeIgniter\Model;

class PlayerModel extends Model
{
  protected $table      = 'players';
  protected $primaryKey = 'entity_id';

  protected $returnType = 'App\Entities\Player';
  protected $useSoftDeletes = false;

  protected $allowedFields = ['entity_id', 'type', 'alive', 'respawn_ticks'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
