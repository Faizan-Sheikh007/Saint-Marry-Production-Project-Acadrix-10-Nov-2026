<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model {
    use HasFactory;

    protected $fillable = [
        'name', 'isbn', 'author', 'category', 'copies', 'available_copies', 'rack',
        'program_id', 'school_class_id', 'industry',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function issues()
    {
        return $this->hasMany(BookIssue::class);
    }

    /** Currently issued (not yet returned) copies of this book. */
    public function activeIssues()
    {
        return $this->hasMany(BookIssue::class)->where('status', 'issued');
    }
}
