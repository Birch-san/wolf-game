<?php
namespace App\Models;

use App\Entities\Hunter;
use CodeIgniter\Model;

class HunterModel extends Model
{
  protected $table      = 'hunters';
  protected $primaryKey = 'entity_id';

  protected $returnType = Hunter::class;
  protected $useSoftDeletes = false;

  protected $allowedFields = ['entity_id', 'reload_ticks', 'pet_ticks', 'bited_ticks'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
