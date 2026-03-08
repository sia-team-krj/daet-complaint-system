<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const BARANGAYS = [
        "Alawihao",
        "Awitan",
        "Bagasbas",
        "Barangay I (Hilahod)",
        "Barangay II (Pasig)",
        "Barangay III (Iraya)",
        "Barangay IV (Mantagbac)",
        "Barangay V (Pandan)",
        "Barangay VI (Centro)",
        "Barangay VII (Diego Liñan)",
        "Barangay VIII (Salcedo)",
        "Bibirao",
        "Borabod",
        "Calasgasan",
        "Camambugan",
        "Cobangbang",
        "Dogongan",
        "Gahonon",
        "Gubat (Moreno, Gubat, Mandulongan)",
        "Lag-on",
        "Magang",
        "Mambalite",
        "Mancruz",
        "Pamorangon",
        "San Isidro",
    ];

    protected $fillable = [
        "first_name",
        "last_name",
        "email",
        "contact_number",
        "barangay",
        "password",
    ];

    protected $hidden = ["password", "remember_token"];

    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
