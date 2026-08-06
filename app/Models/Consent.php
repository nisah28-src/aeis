<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consent extends Model
{
    protected $guarded = [];

    // Bump whenever /pdpa's wording materially changes, so past consent
    // records stay tied to the notice text a candidate actually saw.
    const CURRENT_VERSION = 'v1.0-2026-08-06';
}
