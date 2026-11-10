<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Casts\RsaEncrypted;
Use App\Models\User;
use App\Models\Subject;
use App\Models\SchoolClass;
use App\Models\Course;
use App\Models\LmsAnnouncement;

class Teacher extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'employee_id', 'login_password', 'password_reset_at',
        'name', 'father_name', 'email', 'phone', 'emergency_contact', 'gender', 'date_of_birth',
        'blood_group', 'marital_status', 'address', 'city', 'cnic', 'qualification', 'experience',
        'salary', 'bank_name', 'bank_account_title', 'bank_account_number',
        'subject_id', 'designation', 'department', 'employment_type',
        'joining_date', 'photo', 'signature', 'status', 'timetable_color',
    ];
    protected $casts = [
        'joining_date'      => 'date',
        'date_of_birth'     => 'date',
        'password_reset_at' => 'datetime',
        'login_password'    => 'encrypted', // stored encrypted at rest, decrypted transparently when accessed
        // Sensitive identity fields — RSA+AES envelope encryption
        // (see App\Services\RsaEnvelopeEncryption / App\Casts\RsaEncrypted).
        'cnic'                => RsaEncrypted::class,
        'bank_account_number' => RsaEncrypted::class,
    ];

    protected $hidden = ['login_password', 'cnic', 'bank_account_number'];

    public function user()     { return $this->belongsTo(User::class); }
    public function subject()  { return $this->belongsTo(Subject::class); }

    /**
     * Classes this teacher actually teaches — derived from the class_subject
     * pivot (school_class + subject + teacher), NOT a direct FK on
     * school_classes (that column doesn't exist). Fixes:
     * "Column not found: school_classes.teacher_id".
     */
    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subject', 'teacher_id', 'school_class_id')
                     ->distinct();
    }

    public function courses()  { return $this->hasMany(Course::class); }
    public function lmsAnnouncements() { return $this->hasMany(LmsAnnouncement::class); }

    // ── Admin engagement features ───────────────────────────────────────
    public function messages()    { return $this->hasMany(TeacherMessage::class); }
    public function tours()       { return $this->hasMany(Tour::class); }
    public function projects()    { return $this->hasMany(Project::class); }
    public function practicals()  { return $this->hasMany(Practical::class); }
    public function surveys()     { return $this->hasMany(Survey::class); }
    public function assignmentRequests() { return $this->hasMany(Assignment::class, 'teacher_id')->where('is_admin_request', true); }
    public function quizRequests()       { return $this->hasMany(Quiz::class, 'teacher_id')->where('is_admin_request', true); }
    public function examPapers()         { return $this->hasMany(ExamPaper::class); }
}
