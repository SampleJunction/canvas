<?php

namespace Canvas\Http\Controllers;

use App\Models\Status\PostStatus;
use Canvas\Tag;
use Canvas\Post;
use Canvas\Topic;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controller;
use Canvas\Events\ArticlesUpdated;
use Storage;

class PostController extends Controller
{
    /**
     * Show the posts index page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $posts = Post::select('id', 'title', 'body', 'published_at', 'featured_image', 'created_at')
            ->orderByDesc('created_at')
            ->get();
        return view('canvas::posts.index', compact('posts'));
    }

    /**
     * Show the page to create a new post.
     *
     * @return \Illuminate\View\View
     * @throws \Exception
     */
    public function create()
    {
        $data = [
            'id'     => Uuid::uuid4(),
            'tags'   => Tag::all(['name', 'slug']),
            'topics' => Topic::all(['name', 'slug']),
        ];
        return view('canvas::posts.create', compact('data'));
    }

    /**
     * Show the page to edit a given post.
     *
     * @param string $id
     * @return \Illuminate\View\View
     */
    public function edit(string $id)
    {
        $post = Post::findOrFail($id);
        $data = [
            'post'   => $post,
            'meta'   => $post->meta,
            'tags'   => Tag::all(['name', 'slug']),
            'topics' => Topic::all(['name', 'slug']),
        ];
        if($data['post']->meta_tags){
            //$data['meta_tags'] = str_replace('<br />', PHP_EOL, nl2br(implode("\n", json_decode($data['post']->meta_tags, true))));
            $data['meta_tags'] = str_replace('<br />', "\n", nl2br(implode("\n", json_decode($data['post']->meta_tags, true))));
            $data['meta_tags'] = preg_replace('/[\r\n]+/', "\n", $data['meta_tags']);
        }
        return view('canvas::posts.edit', compact('data','post'));
    }

    /**
     * Save a new post.
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function store()
    {
        $thumbnail = [];
        if(request()->hasFile('thumbnail_image')){
            $file = request()->file('thumbnail_image');
            $file_format = explode('.',$file->getClientOriginalName())[1];
            $file_name = strtotime('now').'_'.str_random(10).".$file_format";
            //$uploadPath = storage_path().DIRECTORY_SEPARATOR.'app\public\article\images'.DIRECTORY_SEPARATOR;
            $uploadPath = storage_path('app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.config('canvas.thumbnail_storage_path'));
            $file->move( $uploadPath, $file_name);
            if(gethostname() == "app2"){
                Storage::disk('sftp')->put('/storage/app/public/article/images/'.$file_name, $uploadPath.$file_name);
            }
            $thumbnail['thumbnail_image'] = '/storage/article/images/'.$file_name;
            $thumbnail['thumbnail_image_caption'] = null;
        }
        $data = [
            'id'                     => request('id'),
            'slug'                   => request('slug'),
            'title'                  => request('title', 'Post Title'),
            'summary'                => request('summary', null),
            'body'                   => request('body', null),
            'published_at'           => Carbon::parse(request('published_at'))->toDateTimeString(),
            'featured_image'         => request('featured_image', null),
            'featured_image_caption' => request('featured_image_caption', null),
            'user_id'                => auth()->user()->id,
            'meta'                   => [
                'meta_description'    => request('meta_description', null),
                'og_title'            => request('og_title', null),
                'og_description'      => request('og_description', null),
                'twitter_title'       => request('twitter_title', null),
                'twitter_description' => request('twitter_description', null),
                'canonical_link'      => request('canonical_link', null),
            ],
            'meta_tags'                => json_encode(explode(PHP_EOL, request()->meta_tags)),
        ];
        $data = array_merge($data, $thumbnail);
        $messages = [
            'required' => __('canvas::validation.required'),
            'unique'   => __('canvas::validation.unique'),
        ];
        validator($data, [
            'title'        => 'required',
            'slug'         => 'required|'.Rule::unique('canvas_posts', 'slug')->ignore(request('id')).'|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/i',
            'published_at' => 'required|date',
            'user_id'      => 'required',
        ], $messages)->validate();
        $data['post_status'] = "pending";
        $post = new Post(['id' => request('id')]);
        $post->fill($data);
        $post->meta = $data['meta'];
        $post->save();

        $post->tags()->sync(
            $this->attachOrCreateTags(request('tags') ?? [])
        );

        $post->topic()->sync(
            $this->attachOrCreateTopic(request('topic') ?? [])
        );
        event(new ArticlesUpdated());
        return redirect(route('canvas.post.edit', $post->id))->with('notify', __('canvas::nav.notify.success'));
    }

    /**
     * Save a given post.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function update(string $id)
    {
        $post = Post::findOrFail($id);
        $thumbnail = [];
        if(request()->hasFile('thumbnail_image')){
            $file = request()->file('thumbnail_image');
            $file_format = explode('.',$file->getClientOriginalName())[1];
            $file_name = strtotime('now').'_'.str_random(10).".$file_format";
            /*$uploadPath = storage_path().DIRECTORY_SEPARATOR.'app\public\article\images'.DIRECTORY_SEPARATOR;*/
            /*$uploadPath = storage_path().DIRECTORY_SEPARATOR.'app\public\article\images'.DIRECTORY_SEPARATOR;*/
			 $uploadPath = storage_path('app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.config('canvas.thumbnail_storage_path'));
            $file->move( $uploadPath, $file_name);
            if(gethostname() == "app2"){
                Storage::disk('sftp')->put('/storage/app/public/article/images/'.$file_name, $uploadPath.$file_name);
            }
            $thumbnail['thumbnail_image'] = '/storage/article/images/'.$file_name;
        }
        $thumbnail['thumbnail_image_caption'] =  request('thumbnail_image_caption',null);
        $data = [
            'id'                     => request('id'),
            'slug'                   => request('slug'),
            'title'                  => request('title', 'Post Title'),
            'summary'                => request('summary', null),
            'body'                   => request('body', null),
            'published_at'           => Carbon::parse(request('published_at'))->toDateTimeString(),
            'featured_image'         => request('featured_image', null),
            'featured_image_caption' => request('featured_image_caption', null),
            'post_status'            => request('post_status', null),
            'user_id'                => $post->user->id,
            'meta'                   => [
                'meta_description'    => request('meta_description', null),
                'og_title'            => request('og_title', null),
                'og_description'      => request('og_description', null),
                'twitter_title'       => request('twitter_title', null),
                'twitter_description' => request('twitter_description', null),
                'canonical_link'      => request('canonical_link', null),
            ],
            'meta_tags'                => json_encode(explode(PHP_EOL, request()->meta_tags)),
        ];
        $data = array_merge($data, $thumbnail);
        $messages = [
            'required' => __('canvas::validation.required'),
            'unique'   => __('canvas::validation.unique'),
        ];

        validator($data, [
            'title'        => 'required',
            'slug'         => 'required|'.Rule::unique('canvas_posts', 'slug')->ignore($id).'|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/i',
            'published_at' => 'required',
            'user_id'      => 'required',
        ], $messages)->validate();
        $post->fill($data);
        $post->meta = $data['meta'];
        $post->save();

        $post->tags()->sync(
            $this->attachOrCreateTags(request('tags') ?? [])
        );

        $post->topic()->sync(
            $this->attachOrCreateTopic(request('topic') ?? [])
        );
        event(new ArticlesUpdated());
        return redirect(route('canvas.post.edit', $post->id))->with('notify', __('canvas::nav.notify.success'));
    }

    /**
     * Delete a given post.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $id)
    {
        $post = Post::findOrFail($id);
        $post->delete();

        return redirect(route('canvas.post.index'));
    }

    /**
     * Attach or create tags given an incoming array.
     *
     * @param array $incomingTags
     * @return array
     *
     * @author Mohamed Said <themsaid@gmail.com>
     */
    private function attachOrCreateTags(array $incomingTags): array
    {
        $tags = Tag::all();

        return collect($incomingTags)->map(function ($incomingTag) use ($tags) {
            $tag = $tags->where('slug', $incomingTag['slug'])->first();

            if (! $tag) {
                $tag = Tag::create([
                    'id'   => $id = Uuid::uuid4(),
                    'name' => $incomingTag['name'],
                    'slug' => $incomingTag['slug'],
                ]);
            }

            return (string) $tag->id;
        })->toArray();
    }

    /**
     * Attach or create a topic given an incoming array.
     *
     * @param array $incomingTopic
     * @return array
     * @throws \Exception
     */
    private function attachOrCreateTopic(array $incomingTopic): array
    {
        if ($incomingTopic) {
            $topic = Topic::where('slug', $incomingTopic['slug'])->first();

            if (! $topic) {
                $topic = Topic::create([
                    'id'   => $id = Uuid::uuid4(),
                    'name' => $incomingTopic['name'],
                    'slug' => $incomingTopic['slug'],
                ]);
            }

            return collect((string) $topic->id)->toArray();
        } else {
            return [];
        }
    }
}
