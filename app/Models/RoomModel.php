<?php
namespace App\Models;

use App\Entities\Room;
use CodeIgniter\Model;

class RoomModel extends Model
{
  protected $table      = 'rooms';
  protected $primaryKey = 'name';

  protected $returnType = Room::class;
  protected $useSoftDeletes = false;

  protected $allowedFields = ['name', 'update_freq_ms', /* 'last_updated', */ 'terrain'];

  protected $validationRules    = [];
  protected $validationMessages = [];
  protected $skipValidation     = false;
}
