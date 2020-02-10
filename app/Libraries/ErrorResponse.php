<?php


class ErrorResponse
{
  /** @var string */
  public $error;
  /** @var string */
  public $message;

  public function __construct(string $error, string $message)
  {
    $this->error = $error;
    $this->message = $message;
  }
}
