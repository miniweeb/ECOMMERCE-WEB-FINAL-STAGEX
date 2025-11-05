<?php

namespace PHPMailer\PHPMailer;

use Exception as GlobalException;

class Exception extends GlobalException
{
    public function errorMessage(): string
    {
        return '<strong>' . htmlspecialchars($this->getMessage(), ENT_COMPAT | ENT_HTML401) . '</strong><br />\n';
    }
}