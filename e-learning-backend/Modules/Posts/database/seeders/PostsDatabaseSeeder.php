<?php

namespace Modules\Posts\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Posts\Models\Post;
use Modules\Posts\Models\PostCategory;
use Modules\Posts\Models\PostComment;
use Modules\Posts\Models\Tag;
use Modules\Users\Models\User;

class PostsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::first()?->id ?? 1;

        // Post categories
        $categoryData = [
            ['name' => 'Công nghệ', 'slug' => 'cong-nghe', 'description' => 'Tin tức công nghệ mới nhất'],
            ['name' => 'Lập trình', 'slug' => 'lap-trinh', 'description' => 'Hướng dẫn và kiến thức lập trình'],
            ['name' => 'Kỹ năng mềm', 'slug' => 'ky-nang-mem', 'description' => 'Phát triển bản thân'],
            ['name' => 'Thông báo', 'slug' => 'thong-bao', 'description' => 'Thông báo từ hệ thống'],
        ];
        foreach ($categoryData as $cat) {
            PostCategory::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        // Tags
        $tagNames = ['Laravel', 'VueJS', 'React', 'PHP', 'JavaScript', 'Python', 'DevOps', 'Career', 'IELTS', 'Tips'];
        foreach ($tagNames as $tagName) {
            Tag::updateOrCreate(['name' => $tagName], ['slug' => Str::slug($tagName)]);
        }

        $allCategories = PostCategory::all()->keyBy('slug');
        $allTags = Tag::all()->keyBy('name');

        $posts = $this->postData();

        foreach ($posts as $data) {
            $post = Post::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'slug' => $data['slug'],
                    'content' => $data['content'],
                    'thumbnail' => $data['thumbnail'],
                    'author_id' => $adminId,
                    'post_category_id' => $allCategories[$data['category']]?->id,
                    'is_published' => true,
                    'published_at' => now()->subDays($data['days_ago']),
                    'views' => rand(50, 1500),
                ]
            );

            // Attach tags
            $tagIds = collect($data['tags'])->map(fn ($t) => $allTags[$t]?->id)->filter()->values()->toArray();
            $post->tags()->sync($tagIds);

            // Add 2–4 comments
            for ($j = 1; $j <= rand(2, 4); $j++) {
                PostComment::firstOrCreate(
                    ['post_id' => $post->id, 'content' => "Bình luận mẫu #{$j} cho bài '{$post->title}'"],
                    [
                        'user_id' => $adminId,
                        'user_type' => 'admin',
                        'is_approved' => (bool) rand(0, 1),
                    ]
                );
            }
        }

        $this->command->info('PostsDatabaseSeeder: Seeded '.count($posts).' posts.');
    }

    private function postData(): array
    {
        $p = '<p>';
        $ep = '</p>';

        return [
            [
                'title' => 'Laravel 12 có gì mới? Những tính năng nổi bật bạn cần biết',
                'slug' => 'laravel-12-tinh-nang-noi-bat',
                'category' => 'lap-trinh',
                'tags' => ['Laravel', 'PHP'],
                'days_ago' => 5,
                'thumbnail' => 'https://picsum.photos/seed/laravel12/800/450',
                'content' => $p.'Laravel 12 vừa ra mắt với hàng loạt cải tiến đáng chú ý, từ hiệu năng routing được tối ưu đến cú pháp Eloquent gọn gàng hơn. Đây là bản phát hành lớn nhất trong lịch sử framework PHP phổ biến này.'.$ep.
                    $p.'Trong bài viết này, chúng ta sẽ cùng khám phá các tính năng mới: Lazy Collections được nâng cấp, Model casts kiểu mới, và cải tiến đáng kể trong hệ thống Queue. Nếu bạn đang dùng Laravel 11, việc nâng cấp rất đơn giản và không phá vỡ backward compatibility.'.$ep.
                    $p.'Đặc biệt, Laravel 12 tích hợp chặt chẽ hơn với Reverb (WebSocket server) và Folio (file-based routing), giúp developer xây dựng ứng dụng real-time nhanh hơn bao giờ hết.'.$ep,
            ],
            [
                'title' => 'Lộ trình học Vue 3 từ zero đến có việc làm trong 6 tháng',
                'slug' => 'lo-trinh-hoc-vue-3-tu-zero-den-co-viec',
                'category' => 'lap-trinh',
                'tags' => ['VueJS', 'JavaScript'],
                'days_ago' => 12,
                'thumbnail' => 'https://picsum.photos/seed/vue3/800/450',
                'content' => $p.'Vue.js 3 là một trong những frontend framework được ưa chuộng nhất hiện nay, đặc biệt tại thị trường Việt Nam. Composition API mang lại cách viết code linh hoạt và tái sử dụng cao hơn so với Options API của Vue 2.'.$ep.
                    $p.'Lộ trình 6 tháng được chia thành 3 giai đoạn: Tháng 1-2 học nền tảng HTML/CSS/JavaScript ES6+. Tháng 3-4 học Vue 3 core, Vue Router, và Pinia. Tháng 5-6 thực hành dự án thực tế và tích hợp REST API.'.$ep.
                    $p.'Với sự hỗ trợ của cộng đồng Vue.js Việt Nam ngày càng lớn mạnh, đây là thời điểm tuyệt vời để bắt đầu. Hàng trăm công ty tuyển dụng Vue.js developer với mức lương từ 15-30 triệu đồng/tháng.'.$ep,
            ],
            [
                'title' => 'Docker Compose vs Kubernetes: Khi nào nên dùng cái nào?',
                'slug' => 'docker-compose-vs-kubernetes-khi-nao-dung',
                'category' => 'cong-nghe',
                'tags' => ['DevOps'],
                'days_ago' => 20,
                'thumbnail' => 'https://picsum.photos/seed/docker/800/450',
                'content' => $p.'Đây là câu hỏi mà hầu hết developer khi bước vào thế giới container đều gặp phải. Docker Compose và Kubernetes đều quản lý container, nhưng ở quy mô và mục đích rất khác nhau.'.$ep.
                    $p.'Docker Compose phù hợp cho môi trường development local, staging nhỏ, hoặc production với 1-3 server. Cấu hình đơn giản bằng một file docker-compose.yml, dễ debug, không cần infrastructure phức tạp.'.$ep.
                    $p.'Kubernetes (K8s) dành cho production scale lớn: auto-scaling, self-healing, rolling deployment, và multi-region. Chi phí vận hành cao hơn nhưng đổi lại là độ tin cậy và khả năng mở rộng vượt trội. Quy tắc đơn giản: dưới 5 server thì dùng Compose, trên 5 server thì cân nhắc K8s.'.$ep,
            ],
            [
                'title' => '5 kỹ năng thiết yếu cho lập trình viên backend năm 2026',
                'slug' => '5-ky-nang-thiet-yeu-cho-backend-developer-2026',
                'category' => 'ky-nang-mem',
                'tags' => ['Career', 'Tips'],
                'days_ago' => 30,
                'thumbnail' => 'https://picsum.photos/seed/backend2026/800/450',
                'content' => $p.'Năm 2026, thị trường backend developer tiếp tục nóng nhưng cũng đòi hỏi cao hơn. Ngoài việc biết một ngôn ngữ lập trình, nhà tuyển dụng kỳ vọng gì ở một backend developer cấp trung?'.$ep.
                    $p.'Năm kỹ năng không thể thiếu: (1) API Design chuẩn RESTful/GraphQL; (2) Database optimization — indexing, query planning; (3) Bảo mật cơ bản — OWASP Top 10, JWT, OAuth2; (4) CI/CD và containerization với Docker; (5) Hiểu về cloud services — AWS/GCP cơ bản.'.$ep.
                    $p.'Quan trọng không kém là kỹ năng mềm: đọc hiểu requirement, viết tài liệu kỹ thuật rõ ràng, và làm việc hiệu quả trong môi trường Agile. Developer giỏi kỹ thuật nhưng thiếu kỹ năng mềm khó thăng tiến.'.$ep,
            ],
            [
                'title' => 'IELTS 7.0 trong 6 tháng: Lộ trình và tài liệu hiệu quả nhất',
                'slug' => 'ielts-7-trong-6-thang-lo-trinh-tai-lieu',
                'category' => 'ky-nang-mem',
                'tags' => ['IELTS', 'Tips'],
                'days_ago' => 45,
                'thumbnail' => 'https://picsum.photos/seed/ielts/800/450',
                'content' => $p.'Đạt IELTS 7.0 trong 6 tháng là mục tiêu hoàn toàn khả thi nếu bạn đang ở band 5.5-6.0 và có kế hoạch học đúng hướng. Bí quyết không phải là học nhiều mà là học đúng.'.$ep.
                    $p.'Tháng 1-2: Xây nền tảng — Cambridge IELTS books (vol 17-18), luyện phát âm với Elsa Speak, học 10 từ vựng học thuật/ngày. Tháng 3-4: Luyện kỹ năng — Reading strategies (skimming/scanning), Listening note-taking, Writing Task 1 Academic. Tháng 5-6: Mock tests và fix điểm yếu.'.$ep.
                    $p.'Tài liệu không thể thiếu: Cambridge IELTS 15-18, Vocabulary for IELTS (Collins), và IELTS Liz website miễn phí. Quan trọng nhất: luyện đề thật hàng tuần và track điểm tiến bộ.'.$ep,
            ],
            [
                'title' => 'Clean Code trong PHP: 10 nguyên tắc giúp code dễ bảo trì',
                'slug' => 'clean-code-php-10-nguyen-tac',
                'category' => 'lap-trinh',
                'tags' => ['PHP', 'Laravel', 'Tips'],
                'days_ago' => 60,
                'thumbnail' => 'https://picsum.photos/seed/cleancode/800/450',
                'content' => $p.'Code hoạt động được chỉ là điều kiện cần, không phải điều kiện đủ. Code tốt phải dễ đọc, dễ test, và dễ bảo trì. Đây là 10 nguyên tắc Clean Code áp dụng trong PHP và Laravel mà mọi developer nên biết.'.$ep.
                    $p."Top 5 nguyên tắc quan trọng nhất: (1) Đặt tên biến/hàm mô tả đủ nghĩa — không dùng \$a, \$temp; (2) Hàm chỉ làm một việc (Single Responsibility); (3) Tránh magic numbers — dùng constants; (4) Comment giải thích WHY không phải WHAT; (5) Không lặp code — DRY (Don't Repeat Yourself).".$ep.
                    $p.'Trong Laravel cụ thể: dùng Form Requests cho validation, Resources cho API response, Services cho business logic phức tạp. Controller chỉ gọi delegate, không chứa business logic.'.$ep,
            ],
            [
                'title' => 'Ra mắt khóa học Python & Machine Learning — Đặc biệt giảm 30%',
                'slug' => 'ra-mat-khoa-hoc-python-machine-learning',
                'category' => 'thong-bao',
                'tags' => ['Python', 'Tips'],
                'days_ago' => 8,
                'thumbnail' => 'https://picsum.photos/seed/pythonml/800/450',
                'content' => $p.'Chúng tôi vui mừng thông báo ra mắt khóa học "Python & Machine Learning Cơ Bản" — được thiết kế dành cho người hoàn toàn chưa có kinh nghiệm lập trình muốn bước vào lĩnh vực Data Science.'.$ep.
                    $p.'Khóa học bao gồm hơn 80 bài học video, 15 bài tập thực hành có chấm điểm tự động, và 5 dự án thực tế: phân tích dữ liệu COVID, dự đoán giá nhà, phân loại email spam, nhận diện chữ số viết tay và chatbot đơn giản.'.$ep.
                    $p.'Nhân dịp ra mắt, toàn bộ học viên đăng ký trong tuần đầu sẽ nhận ưu đãi 30% và quyền truy cập vĩnh viễn cùng certificate hoàn thành. Đừng bỏ lỡ!'.$ep,
            ],
            [
                'title' => 'Tại sao Flutter là lựa chọn hàng đầu cho mobile development 2026?',
                'slug' => 'flutter-lua-chon-hang-dau-mobile-2026',
                'category' => 'cong-nghe',
                'tags' => ['Career'],
                'days_ago' => 75,
                'thumbnail' => 'https://picsum.photos/seed/flutter2026/800/450',
                'content' => $p.'Flutter của Google tiếp tục khẳng định vị thế dẫn đầu trong phát triển ứng dụng di động đa nền tảng. Với hơn 150.000 ứng dụng trên App Store và Google Play, Flutter đã vượt qua React Native về market share tại nhiều thị trường.'.$ep.
                    $p.'Điểm mạnh của Flutter: (1) Một codebase cho iOS, Android, Web, Desktop; (2) Hiệu năng gần native nhờ biên dịch sang native ARM; (3) Hot reload giúp phát triển nhanh; (4) Hệ sinh thái pub.dev phong phú với 30.000+ package.'.$ep.
                    $p.'Tại Việt Nam, nhu cầu tuyển Flutter developer tăng 200% trong 2 năm qua. Mức lương trung bình 20-35 triệu đồng/tháng. Đây là thời điểm vàng để học Flutter.'.$ep,
            ],
            [
                'title' => 'Cách học tiếng Nhật hiệu quả với phương pháp shadowing',
                'slug' => 'hoc-tieng-nhat-phuong-phap-shadowing',
                'category' => 'ky-nang-mem',
                'tags' => ['Tips'],
                'days_ago' => 90,
                'thumbnail' => 'https://picsum.photos/seed/japanese/800/450',
                'content' => $p.'Shadowing là phương pháp học ngoại ngữ bằng cách lặp lại đồng thời (hoặc gần đồng thời) âm thanh nghe được. Được phát triển bởi giáo sư Alexander Arguelles, phương pháp này đặc biệt hiệu quả cho tiếng Nhật — ngôn ngữ có ngữ điệu và nhịp điệu phức tạp.'.$ep.
                    $p.'Cách thực hành: Bước 1 — Nghe bản gốc 3 lần, không nhìn script. Bước 2 — Nhìn script và đọc theo. Bước 3 — Che script và shadowing. Mỗi ngày 20-30 phút, kiên trì trong 3 tháng sẽ thấy kết quả rõ rệt.'.$ep.
                    $p.'Tài liệu tốt nhất để shadowing tiếng Nhật: NHK Web Easy, Japanese Pod 101, và anime có phụ đề tiếng Nhật. Quan trọng là chọn level phù hợp — không quá dễ cũng không quá khó.'.$ep,
            ],
            [
                'title' => 'Tổng kết tháng 5/2026: Top học viên xuất sắc và thành tích nổi bật',
                'slug' => 'tong-ket-thang-5-2026-hoc-vien-xuat-sac',
                'category' => 'thong-bao',
                'tags' => ['Tips'],
                'days_ago' => 2,
                'thumbnail' => 'https://picsum.photos/seed/summary0526/800/450',
                'content' => $p.'Tháng 5/2026 là tháng có lượng học viên hoàn thành khóa học cao nhất kể từ khi nền tảng ra mắt. Tổng cộng hơn 420 certificate đã được cấp, trong đó khóa Laravel và Vue.js dẫn đầu với 95 và 82 certificate.'.$ep.
                    $p.'Top 3 học viên xuất sắc tháng: (1) Nguyễn Thị Mai — hoàn thành 4 khóa trong một tháng, đạt điểm 100% quiz Laravel; (2) Trần Văn Hùng — streak học liên tục 30 ngày; (3) Lê Thị Lan — chia sẻ nhiều nhất trong cộng đồng với 48 câu trả lời hữu ích.'.$ep.
                    $p.'Tháng 6, chúng tôi sẽ ra mắt tính năng Learning Path — lộ trình học được cá nhân hóa theo mục tiêu nghề nghiệp. Cảm ơn toàn bộ học viên đã tin tưởng và đồng hành cùng chúng tôi!'.$ep,
            ],
        ];
    }
}
