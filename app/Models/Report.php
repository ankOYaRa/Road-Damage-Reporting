<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'description',
        'address',
        'latitude',
        'longitude',
        'photo_path',
        'cnn_status',
        'cnn_confidence',
        'admin_status',
        'admin_note',
    ];

    protected $casts = [
        'latitude'        => 'float',
        'longitude'       => 'float',
        'cnn_confidence'  => 'float',
    ];

    public function isValid(): bool
    {
        return $this->cnn_status === 'valid';
    }

    public function confidencePercent(): string
    {
        return round($this->cnn_confidence * 100, 1) . '%';
    }
}
