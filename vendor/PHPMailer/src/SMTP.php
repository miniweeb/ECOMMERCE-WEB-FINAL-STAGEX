<?php
namespace PHPMailer\PHPMailer;

class SMTP
{
    public const VERSION = '0.0.1';
    public const LE = "\r\n";
    public const DEFAULT_PORT = 25;
    public const DEFAULT_SECURE_PORT = 465;
    public const MAX_LINE_LENGTH = 998;
    public const MAX_REPLY_LENGTH = 512;
    public const DEBUG_OFF = 0;
    public const DEBUG_CLIENT = 1;
    public const DEBUG_SERVER = 2;
    public const DEBUG_CONNECTION = 3;
    public const DEBUG_LOWLEVEL = 4;
}