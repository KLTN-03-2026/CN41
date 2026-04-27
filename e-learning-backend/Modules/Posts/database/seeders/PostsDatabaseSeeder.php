<?php

namespace Modules\Posts\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Posts\Models\Post;
use Modules\Posts\Models\PostCategory;
use Modules\Posts\Models\PostComment;
use Modules\Posts\Models\Tag;

class PostsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminId = User::first()?->id ?? 1;

        // Categories
        $categories = [
            ['name' => 'Công nghệ', 'slug' => 'cong-nghe', 'description' => 'Tin tức công nghệ mới nhất'],
            ['name' => 'Lập trình', 'slug' => 'lap-trinh', 'description' => 'Hướng dẫn và kiến thức lập trình'],
            ['name' => 'Kỹ năng mềm', 'slug' => 'ky-nang-mem', 'description' => 'Phát triển bản thân và kỹ năng'],
            ['name' => 'Thông báo', 'slug' => 'thong-bao', 'description' => 'Các thông báo từ hệ thống'],
        ];

        foreach ($categories as $cat) {
            PostCategory::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        // Tags
        $tags = ['Laravel', 'VueJS', 'React', 'PHP', 'JavaScript', 'SQL', 'DevOps', 'Career', 'Tips', 'News'];
        foreach ($tags as $tagName) {
            Tag::updateOrCreate(['name' => $tagName], [
                'slug' => Str::slug($tagName),
            ]);
        }

        $allCategories = PostCategory::all();
        $allTags = Tag::all();

        // Posts
        for ($i = 1; $i <= 10; $i++) {
            $title = "Bài viết mẫu số $i: ".Str::random(10);
            $post = Post::create([
                'title' => $title,
                'slug' => Str::slug($title).'-'.time().'-'.$i,
                'content' => "Đây là nội dung mẫu cho bài viết số $i. <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p><h3>Tiểu đề phụ</h3><p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><pre><code>console.log('Hello World');</code></pre>",
                'thumbnail' => "https://picsum.photos/seed/post$i/800/450",
                'author_id' => $adminId,
                'post_category_id' => $allCategories->random()->id,
                'is_published' => true,
                'published_at' => now(),
                'views' => rand(10, 500),
            ]);

            // Random tags
            $post->tags()->attach($allTags->random(rand(1, 3))->pluck('id'));

            // Random comments
            for ($j = 1; $j <= rand(2, 5); $j++) {
                PostComment::create([
                    'post_id' => $post->id,
                    'user_id' => $adminId,
                    'user_type' => 'admin',
                    'content' => "Bình luận mẫu số $j cho bài viết này. Thật là một bài viết hữu ích!",
                    'is_approved' => rand(0, 1) == 1,
                ]);
            }
        }
    }
}
