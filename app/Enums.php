<?php

namespace App;

final class Enums
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_STAFF = 'staff';
    public const ROLE_INSPECTOR = 'inspector';
    public const ROLE_FACTORY = 'factory';

    public const SERIAL_TYPE_BODY = 'BODY';
    public const SERIAL_TYPE_PCB = 'PCB';

    public const STATUS_UNUSED = 'UNUSED';
    public const STATUS_LINKED = 'LINKED';
    public const STATUS_INSPECTED = 'INSPECTED';
    public const STATUS_VOID = 'VOID';

    public const RESULT_PASS = 'PASS';
    public const RESULT_FAIL = 'FAIL';
}
