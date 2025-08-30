<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'ci',
        'avatar',
        'phone',
        'bio',
        'role',
        'active',
        'password_changed',
        'has_avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Relación con el avatar del usuario
     */
    public function avatar()
    {
        return $this->hasOne(\App\Models\UserAvatar::class);
    }

    /**
     * Relación con las inscripciones de curso (para estudiantes)
     */
    public function enrollments()
    {
        return $this->hasMany(\App\Domain\Course\Models\CourseEnrollment::class, 'student_id');
    }

    /**
     * Relación con los cursos que enseña (para profesores)
     */
    public function teachingCourses()
    {
        return $this->hasMany(\App\Domain\Course\Models\Course::class, 'teacher_id');
    }
}
