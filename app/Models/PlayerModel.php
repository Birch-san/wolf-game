<?php
namespace App\Models;

use App\Entities\Player;
use CodeIgniter\Model;

class PlayerModel extends Model
{
  protected $table      = 'players';
  protected $primaryKey = 'entity_id';

  protected $returnType = Player::class;
  protected $useSoftDeletes = false;

  protected $allowedFields = ['entity_id', 'type', 'alive', 'respawn_ticks', 'score'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
