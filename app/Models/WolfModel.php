<?php
namespace App\Models;

use CodeIgniter\Model;

class WolfModel extends Model
{
  protected $table      = 'wolves';
  protected $primaryKey = 'id';

  protected $returnType = 'App\Entities\Wolf';
  protected $useSoftDeletes = false;

  protected $allowedFields = ['name'];

//  protected $useTimestamps = false;
//  protected $createdField  = 'created_at';
//  protected $updatedField  = 'updated_at';
//  protected $deletedField  = 'deleted_at';

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
