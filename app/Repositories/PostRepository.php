<?php
namespace App\Repositories;

use App\Models\Post;

class PostRepository
{
    protected $model;

    public function __construct(Post $post)
    {
        $this->model = $post;
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function findOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    public function updateStatus($id, $status)
    {
        $post = $this->findOrFail($id);
        $post->status = $status;
        $post->save();

        return $post;
    }

    public function delete($id)
    {
        $post = $this->findOrFail($id);
        return $post->delete();
    }
}
