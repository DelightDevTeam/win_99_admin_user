<?php

namespace App\Enums;

enum UserType: int
{
    case Admin = 10;
    //case Master = 20;
    //case Agent = 30;
    case Player = 20;

    public static function usernameLength(UserType $type)
    {
        return match ($type) {
            self::Admin => 1,
           // self::Master => 2,
            //self::Agent => 3,
            self::Player => 2,
        };
    }

    public static function childUserType(UserType $type)
    {
        return match ($type) {
            self::Admin => self::Player,
            // self::Admin => self::Master,
           // self::Master => self::Agent,
            //self::Agent => self::Player,
            self::Player => self::Player
        };
    }
}
