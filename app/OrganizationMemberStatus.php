<?php

namespace App;

enum OrganizationMemberStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Banned = 'banned';
}
