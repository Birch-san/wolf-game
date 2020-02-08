<?php
namespace App\Entities;

use CodeIgniter\Entity;

/**
 * Class Player
 * @package App\Entities
 * @property string id
 * @property string entity_id
 * @property string type
 * @property boolean alive
 * @property int respawn_ticks
 * @property int score
 */
class Player extends Entity
{
}
