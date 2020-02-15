<?php


namespace App\Libraries;


interface ErrorCodes
{
  public const CLIENT_ERROR = 'CLIENT_ERROR';
  public const USER_NOT_IN_ROOM = 'USER_NOT_IN_ROOM';
  public const NOT_LOGGED_IN = 'NOT_LOGGED_IN';
  public const FAILED_QUERY = 'FAILED_QUERY';
}
