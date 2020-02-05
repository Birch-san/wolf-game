<?php
namespace App\Models;

use CodeIgniter\Model;

class WolfModel extends Model
{
  protected $table      = 'wolves';
  protected $primaryKey = 'entity_id';

  protected $returnType = 'App\Entities\Wolf';
  protected $useSoftDeletes = false;

  protected $allowedFields = ['entity_id', 'howling'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
