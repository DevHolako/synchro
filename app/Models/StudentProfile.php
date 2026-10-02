<?php

namespace App\Models;

use Database\Factories\StudentProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $last_name
 * @property string|null $first_name
 * @property int|null $student_group_id
 * @property string|null $student_number
 * @property string|null $phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read StudentGroup|null $studentGroup
 */
#[Fillable(['user_id', 'last_name', 'first_name', 'student_group_id', 'student_number', 'phone'])]
class StudentProfile extends Model
{
    /** @use HasFactory<StudentProfileFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The display name given to a student account: given name, then surname in capitals.
     */
    public static function displayName(string $firstName, string $lastName): string
    {
        return trim(trim($firstName).' '.mb_strtoupper(trim($lastName)));
    }

    /**
     * @return BelongsTo<StudentGroup, $this>
     */
    public function studentGroup(): BelongsTo
    {
        return $this->belongsTo(StudentGroup::class);
    }
}
