<?php
namespace App\Models;

use App\Entities\User;
use CodeIgniter\Model;

class UserModel extends Model
{
  protected $table      = 'users';
  protected $primaryKey = 'id';

  protected $returnType = User::class;
  protected $useSoftDeletes = false;

  protected $allowedFields = ['id', 'name'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
