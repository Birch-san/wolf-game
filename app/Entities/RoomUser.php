<?php
namespace App\Entities;

use CodeIgniter\Entity;

class RoomUser extends Entity
{
  protected $dates = ['joined', 'latest_poll'];
}
