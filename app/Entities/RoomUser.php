<?php
namespace App\Entities;

use CodeIgniter\Entity;
use CodeIgniter\I18n\Time;

/**
 * Class RoomUser
 * @package App\Entities
 * @property string id
 * @property string room_id
 * @property string user_id
 * @property Time joined
 * @property Time latest_poll
 */
class RoomUser extends Entity
{
  protected $dates = ['joined', 'latest_poll'];
}
