<?php


class ErrorResponse
{
  /** @var string */
  public $message;

  public function __construct(string $message)
  {
    $this->message = $message;
  }
}
