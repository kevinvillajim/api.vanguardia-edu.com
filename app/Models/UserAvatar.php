<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAvatar extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'avatar_base64',
        'mime_type',
        'file_size',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener la URL de datos del avatar
     */
    public function getDataUrlAttribute(): string
    {
        return "data:{$this->mime_type};base64,{$this->avatar_base64}";
    }

    /**
     * Obtener el tamaño formateado
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB'];
        
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
