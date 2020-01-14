<?php

class JsonUtils
{
  public static function jsonRespond($data=null, $httpStatus=200)
  {
    header_remove();

    header("Content-Type: application/json;charset=utf-8");

    header('Status: ' . $httpStatus);

    echo json_encode($data);

    exit();
  }
}
