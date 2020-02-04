<?php


class IdGenerator
{
  public static function generateId(): string {
    return bin2hex(random_bytes(18));
  }
}
