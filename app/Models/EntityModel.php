<?php
namespace App\Models;

use App\Entities\Entity;
use CodeIgniter\Model;

class EntityModel extends Model
{
  protected $table      = 'entities';
  protected $primaryKey = 'id';

  protected $returnType = Entity::class;
  protected $useSoftDeletes = false;

  protected $allowedFields = ['id', 'room_id', 'user_id', 'type', 'pos_x', 'pos_y'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
