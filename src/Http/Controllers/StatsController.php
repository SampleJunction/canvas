<?php

namespace Canvas\Http\Controllers;

use Canvas\Post;
use Canvas\View;
use Canvas\Trends;
use Illuminate\Routing\Controller;

class StatsController extends Controller
{
    use Trends;

    /**
     * Number of days in the past to generate stats for.
     *
     * @const int
     */
    const DAYS_PRIOR = 30;

    /**
     * Show the stats index page with post and view data.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $published = Post::select('id', 'title', 'body', 'published_at', 'created_at','post_status')
            ->published()
            ->orderByDesc('created_at')
            ->withCount('views')
            ->get();
        $status_count = [];
        foreach(config('canvas.canvas_status') as $name => $value){
            $count = [];
            foreach($published as $post){
                if($post->post_status==$name){
                    $count[] = $post->id;
                    $status_count[$name] = $count;
                }
            }
        }
        foreach ($status_count as $key => $value){
            $status_count[$key] = count($value);
        }

        // Append the estimated reading time
        $published->each->append('read_time');

        // Get views for the last [X] days
        $views = View::whereBetween('created_at', [
            now()->subDays(self::DAYS_PRIOR)->toDateTimeString(),
            now()->toDateTimeString(),
        ])->select('created_at')->get();
        $data = [
            'posts' => [
                'all'             => $published,
                'published_count' => $published->count(),
                'drafts_count'    => Post::draft()->count(),
                'status_count'    => $status_count,
            ],
            'views' => [
                'count' => $views->count(),
                'trend' => json_encode($this->getViewTrends($views, self::DAYS_PRIOR)),
            ],
        ];

        return view('canvas::stats.index', compact('data'));
    }

    /**
     * Show the stats page for a given post.
     *
     * @param string $id
     * @return \Illuminate\View\View
     */
    public function show(string $id)
    {
        $post = Post::findOrFail($id);

        if ($post->published) {
            $data = [
                'post'                  => $post,
                'traffic'               => $post->top_referers,
                'popular_reading_times' => $post->popular_reading_times,
                'views'                 => json_encode($this->getViewTrends($post->views, self::DAYS_PRIOR)),
            ];

            return view('canvas::stats.show', compact('data'));
        } else {
            abort(404);
        }
    }
}
