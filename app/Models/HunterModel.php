<?php
namespace App\Models;

use CodeIgniter\Model;

class HunterModel extends Model
{
  protected $table      = 'hunters';
  protected $primaryKey = 'entity_id';

  protected $returnType = 'App\Entities\Hunter';
  protected $useSoftDeletes = false;

  protected $allowedFields = ['entity_id', 'reload_ticks'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
