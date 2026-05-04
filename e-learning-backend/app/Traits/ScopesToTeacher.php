<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopesToTeacher
{
    /**
     * Boot the trait.
     */
    protected static function bootScopesToTeacher()
    {
        static::addGlobalScope('teacher_scope', function (Builder $builder) {
            // Chỉ áp dụng scope nếu đang login qua guard admin
            if (auth('admin')->check()) {
                $user = auth('admin')->user();

                // Nếu là super-admin thì bỏ qua scope, xem được tất cả
                if ($user->hasRole('super-admin')) {
                    return;
                }

                // Nếu là teacher, lấy teacher_id của họ
                if ($user->hasRole('teacher')) {
                    $teacherProfile = $user->teacher;
                    if ($teacherProfile) {
                        // Nếu là model Course (có cột teacher_id)
                        if (in_array('teacher_id', $builder->getModel()->getFillable())) {
                            $builder->where($builder->getModel()->getTable().'.teacher_id', $teacherProfile->id);
                        }
                        // Nếu là model Lesson hoặc Section (thuộc Course)
                        elseif (in_array('course_id', $builder->getModel()->getFillable())) {
                            $builder->whereHas('course', function ($query) use ($teacherProfile) {
                                $query->withoutGlobalScope('teacher_scope')
                                    ->where('courses.teacher_id', $teacherProfile->id);
                            });
                        }
                    } else {
                        // Nếu là teacher nhưng chưa có profile, chặn không cho xem gì cả
                        $builder->whereRaw('1 = 0');
                    }
                }
            }
        });
    }
}
