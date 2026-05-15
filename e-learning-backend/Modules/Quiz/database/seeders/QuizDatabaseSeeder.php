<?php

namespace Modules\Quiz\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Course\Models\Course;
use Modules\Lessons\Models\Lesson;
use Modules\Lessons\Models\Section;
use Modules\Quiz\Models\Quiz;
use Modules\Quiz\Models\QuizQuestion;

class QuizDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $targets = $this->quizData();

        foreach ($targets as $target) {
            $course = Course::where('name', $target['course_name'])->first();
            if (! $course) {
                $this->command->warn("QuizDatabaseSeeder: Course not found — {$target['course_name']}");

                continue;
            }

            $firstSection = Section::where('course_id', $course->id)
                ->orderBy('order')
                ->first();
            if (! $firstSection) {
                continue;
            }

            // The 3rd lesson in any section (index 2) is always type=document per LessonDatabaseSeeder pattern
            $lesson = Lesson::where('section_id', $firstSection->id)
                ->where('type', 'document')
                ->orderBy('order')
                ->first();
            if (! $lesson) {
                continue;
            }

            // Mark lesson as quiz type
            $lesson->update(['type' => 'quiz']);

            $quiz = Quiz::create([
                'lesson_id' => $lesson->id,
                'title' => $target['title'],
                'description' => $target['description'],
                'max_attempts' => 3,
                'time_limit' => 10,
                'status' => 1,
            ]);

            foreach ($target['questions'] as $order => $q) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question' => $q['q'],
                    'option_a' => $q['a'],
                    'option_b' => $q['b'],
                    'option_c' => $q['c'],
                    'option_d' => $q['d'],
                    'correct_option' => $q['correct'],
                    'order' => $order + 1,
                ]);
            }

            $this->command->info("QuizDatabaseSeeder: Created quiz '{$quiz->title}' with 5 questions.");
        }
    }

    private function quizData(): array
    {
        return [
            [
                'course_name' => 'Laravel 12 Từ Cơ Bản Đến Nâng Cao',
                'title' => 'Kiểm tra kiến thức Laravel cơ bản',
                'description' => 'Bài kiểm tra sau khi hoàn thành Chương 1. Thời gian: 10 phút, 5 câu hỏi.',
                'questions' => [
                    [
                        'q' => 'Middleware trong Laravel có vai trò chính là gì?',
                        'a' => 'Quản lý kết nối database',
                        'b' => 'Lọc và xử lý HTTP request trước khi vào controller',
                        'c' => 'Render Blade template',
                        'd' => 'Gửi email thông báo',
                        'correct' => 'B',
                    ],
                    [
                        'q' => 'Lệnh Artisan nào tạo một Controller mới?',
                        'a' => 'php artisan make:model',
                        'b' => 'php artisan new:controller',
                        'c' => 'php artisan make:controller',
                        'd' => 'php artisan create:controller',
                        'correct' => 'C',
                    ],
                    [
                        'q' => 'Trong Eloquent, quan hệ hasMany diễn tả điều gì?',
                        'a' => 'Một model thuộc về nhiều model khác',
                        'b' => 'Quan hệ nhiều-nhiều',
                        'c' => 'Một model có nhiều bản ghi liên kết ở bảng khác',
                        'd' => 'Quan hệ một-một',
                        'correct' => 'C',
                    ],
                    [
                        'q' => 'CSRF token trong Laravel bảo vệ chống lại tấn công nào?',
                        'a' => 'SQL Injection',
                        'b' => 'Cross-Site Request Forgery',
                        'c' => 'XSS (Cross-Site Scripting)',
                        'd' => 'Man-in-the-Middle',
                        'correct' => 'B',
                    ],
                    [
                        'q' => 'Câu lệnh nào chạy tất cả migration chưa được thực thi?',
                        'a' => 'php artisan db:seed',
                        'b' => 'php artisan schema:up',
                        'c' => 'php artisan migrate:fresh',
                        'd' => 'php artisan migrate',
                        'correct' => 'D',
                    ],
                ],
            ],
            [
                'course_name' => 'Vue.js 3 & Pinia Thực Chiến',
                'title' => 'Kiểm tra kiến thức Vue 3',
                'description' => 'Bài kiểm tra sau khi hoàn thành Chương 1. Thời gian: 10 phút, 5 câu hỏi.',
                'questions' => [
                    [
                        'q' => 'Cú pháp nào dùng để bind attribute một chiều trong Vue 3?',
                        'a' => 'v-model',
                        'b' => 'v-on:attr',
                        'c' => ':attr hoặc v-bind:attr',
                        'd' => 'v-if',
                        'correct' => 'C',
                    ],
                    [
                        'q' => 'Pinia khác Vuex ở điểm quan trọng nào?',
                        'a' => 'Pinia không hỗ trợ TypeScript',
                        'b' => 'Pinia không cần mutations, trực tiếp thay đổi state trong actions',
                        'c' => 'Pinia chỉ dùng được với Vue 2',
                        'd' => 'Pinia chậm hơn Vuex',
                        'correct' => 'B',
                    ],
                    [
                        'q' => 'reactive() trong Vue 3 khác ref() ở điểm nào?',
                        'a' => 'reactive() dùng cho primitive values, ref() cho object',
                        'b' => 'reactive() dùng cho object/array, ref() dùng được cho mọi kiểu dữ liệu',
                        'c' => 'Không có sự khác biệt',
                        'd' => 'reactive() đã lỗi thời và không nên dùng',
                        'correct' => 'B',
                    ],
                    [
                        'q' => 'Directive v-for trong Vue dùng để làm gì?',
                        'a' => 'Tạo điều kiện hiển thị phần tử',
                        'b' => 'Lắng nghe DOM event',
                        'c' => 'Lặp qua danh sách để render nhiều phần tử',
                        'd' => 'Bind dynamic class',
                        'correct' => 'C',
                    ],
                    [
                        'q' => 'defineEmits() trong Vue 3 script setup dùng để làm gì?',
                        'a' => 'Nhận props từ component cha',
                        'b' => 'Khai báo các custom events mà component có thể phát ra',
                        'c' => 'Lắng nghe native DOM events',
                        'd' => 'Định nghĩa computed properties',
                        'correct' => 'B',
                    ],
                ],
            ],
            [
                'course_name' => 'Python & Machine Learning Cơ Bản',
                'title' => 'Kiểm tra kiến thức Python cơ bản',
                'description' => 'Bài kiểm tra sau khi hoàn thành Chương 1. Thời gian: 10 phút, 5 câu hỏi.',
                'questions' => [
                    [
                        'q' => 'Trong Python, kiểu dữ liệu nào là bất biến (immutable)?',
                        'a' => 'list',
                        'b' => 'dict',
                        'c' => 'tuple',
                        'd' => 'set',
                        'correct' => 'C',
                    ],
                    [
                        'q' => 'NumPy được sử dụng chủ yếu để làm gì?',
                        'a' => 'Xây dựng web API với Python',
                        'b' => 'Tính toán số học hiệu năng cao với mảng đa chiều',
                        'c' => 'Tạo giao diện đồ họa',
                        'd' => 'Kết nối và truy vấn database',
                        'correct' => 'B',
                    ],
                    [
                        'q' => 'Pandas DataFrame là gì?',
                        'a' => 'Một loại vòng lặp đặc biệt trong Python',
                        'b' => 'Cấu trúc dữ liệu 2 chiều dạng bảng với nhãn hàng và cột',
                        'c' => 'Thư viện machine learning chính của Python',
                        'd' => 'Web framework tương tự Django',
                        'correct' => 'B',
                    ],
                    [
                        'q' => 'Supervised learning khác unsupervised learning ở điểm nào?',
                        'a' => 'Supervised dùng nhiều dữ liệu hơn',
                        'b' => 'Supervised học từ dữ liệu đã có nhãn, unsupervised tự tìm cấu trúc',
                        'c' => 'Không có sự khác biệt về kết quả',
                        'd' => 'Unsupervised luôn cho kết quả chính xác hơn',
                        'correct' => 'B',
                    ],
                    [
                        'q' => 'Hàm train_test_split() trong Scikit-learn dùng để làm gì?',
                        'a' => 'Huấn luyện model tự động',
                        'b' => 'Chia dataset thành tập train và tập test',
                        'c' => 'Chuẩn hóa (normalize) dữ liệu đầu vào',
                        'd' => 'Đánh giá độ chính xác của model',
                        'correct' => 'B',
                    ],
                ],
            ],
        ];
    }
}
