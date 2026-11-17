<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\Share;
use App\Services\NotificationService;
use App\Services\UploadService;

final class PostController extends Controller
{
    public function show(array $params): void
    {
        $post = Post::find((int) $params['id']);
        $viewerId = auth_id();

        if ($post === null || !Post::canView($post, $viewerId)) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Bài viết không tồn tại']);

            return;
        }

        $this->view('posts/show', [
            'title'   => 'Bài viết',
            'post'    => $post,
            'images'  => PostImage::all($post['id']),
            'comments' => \App\Models\Comment::forPost((int) $post['id']),
            'isOwner' => Post::canManage($post, $viewerId),
            'canonicalUrl' => url('/p/' . (int) $post['id']),
            'metaTags' => $this->openGraphTags($post),
        ]);
    }

    /**
     * Open Graph metadata để hiển thị đẹp khi chia sẻ liên kết ra ngoài.
     *
     * @return array<string,string>
     */
    private function openGraphTags(array $post): array
    {
        $images = PostImage::all((int) $post['id']);
        $tags = [
            'og:type'        => 'article',
            'og:site_name'  => (string) config('app.name', 'Koobecaf'),
            'og:title'       => $post['display_name'] . ' trên ' . config('app.name', 'Koobecaf'),
            'og:description' => excerpt($post['content'], 160),
            'og:url'         => url('/p/' . (int) $post['id']),
        ];

        if ($images !== []) {
            $tags['og:image'] = (string) upload_url($images[0]['path']);
        }

        return $tags;
    }

    public function store(): void
    {
        $userId = auth_id();
        if ($userId === null) {
            Response::redirect('/login');
        }

        $content = trim((string) $this->request->input('content', ''));
        $privacy = $this->request->string('privacy', Post::PRIVACY_PUBLIC);

        $validator = (new Validator($this->request->all()))
            ->in('privacy', [Post::PRIVACY_PUBLIC, Post::PRIVACY_FOLLOWERS, Post::PRIVACY_PRIVATE], 'Quyền riêng tư')
            ->max('content', 5000, 'Nội dung');

        $files = $this->request->files('images');
        if ($content === '' && $files === []) {
            $validator->addError('content', 'Bài viết cần có nội dung hoặc hình ảnh.');
        }

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::back('/');
        }

        $imagePaths = [];
        foreach ($files as $file) {
            try {
                $imagePaths[] = UploadService::image($file, 'posts');
            } catch (\RuntimeException $e) {
                Session::flash('error', $e->getMessage());
                Response::back('/');
            }
        }

        $postId = Database::transaction(static function () use ($userId, $content, $privacy, $imagePaths): int {
            $postId = Post::create($userId, $content, $privacy);
            Post::attachImages($postId, $imagePaths);

            return $postId;
        });

        Session::flash('success', 'Đã đăng bài viết.');
        Response::redirect('/');
    }

    public function edit(array $params): void
    {
        $post = Post::find((int) $params['id']);
        if ($post === null || !Post::canManage($post, auth_id())) {
            http_response_code(403);
            $this->view('errors/403', ['title' => 'Không có quyền']);

            return;
        }

        $this->view('posts/edit', [
            'title'  => 'Chỉnh sửa bài viết',
            'post'   => $post,
            'images' => PostImage::all((int) $post['id']),
        ]);
    }

    public function update(array $params): void
    {
        $post = Post::find((int) $params['id']);
        if ($post === null || !Post::canManage($post, auth_id())) {
            http_response_code(403);
            $this->view('errors/403', ['title' => 'Không có quyền']);

            return;
        }

        $content = trim((string) $this->request->input('content', ''));
        $privacy = $this->request->string('privacy', Post::PRIVACY_PUBLIC);

        $validator = (new Validator($this->request->all()))
            ->required('content', 'Nội dung')
            ->max('content', 5000, 'Nội dung')
            ->in('privacy', [Post::PRIVACY_PUBLIC, Post::PRIVACY_FOLLOWERS, Post::PRIVACY_PRIVATE], 'Quyền riêng tư');

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::redirect('/posts/' . (int) $post['id'] . '/edit');
        }

        Post::update((int) $post['id'], $content, $privacy);

        $removeIds = $this->request->input('remove_images', []);
        if (is_array($removeIds)) {
            foreach ($removeIds as $imageId) {
                $image = PostImage::find((int) $imageId);
                if ($image !== null && (int) $image['post_id'] === (int) $post['id']) {
                    UploadService::delete((string) $image['path']);
                    PostImage::delete((int) $image['id']);
                }
            }
        }

        foreach ($this->request->files('images') as $file) {
            try {
                $path = UploadService::image($file, 'posts');
                Post::attachImages((int) $post['id'], [$path]);
            } catch (\RuntimeException $e) {
                Session::flash('error', $e->getMessage());
            }
        }

        Session::flash('success', 'Đã cập nhật bài viết.');
        Response::redirect('/post/' . (int) $post['id']);
    }

    public function destroy(array $params): void
    {
        $post = Post::find((int) $params['id']);
        if ($post === null || !Post::canManage($post, auth_id())) {
            http_response_code(403);
            $this->view('errors/403', ['title' => 'Không có quyền']);

            return;
        }

        foreach (PostImage::all((int) $post['id']) as $image) {
            UploadService::delete((string) $image['path']);
        }

        Post::delete((int) $post['id']);

        Session::flash('success', 'Đã xóa bài viết.');
        Response::redirect('/');
    }
}
