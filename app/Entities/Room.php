<?php
namespace App\Entities;

use CodeIgniter\Entity;
use CodeIgniter\I18n\Time;

/**
 * Class Room
 * @package App\Entities
 * @property string name
 * @property int update_freq_ms
 * @property Time last_updated
 * @property int[][] terrain
 */
class Room extends Entity
{
  protected $dates = ['last_updated'];
  protected $casts = ['terrain' => 'json-array'];
}
