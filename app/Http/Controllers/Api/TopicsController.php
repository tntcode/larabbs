<?php

namespace App\Http\Controllers\Api;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use App\Transformers\TopicTransformer;
use App\Http\Requests\Api\TopicRequest;

class TopicsController extends Controller
{

    /**
     * 话题列表
     * @param  Request $request [description]
     * @param  Topic   $topic   [description]
     * @return [type]           [description]
     */
    public function index(Request $request, Topic $topic)
    {
        $query = $topic->query();

        if ($categoryId = $request->category_id) {
            $query->where('category_id', $categoryId);
        }

        // 为了说明 N+1问题，不使用 scopeWithOrder
        switch ($request->order) {
            case 'recent':
                $query->recent();
                break;

            default:
                $query->recentReplied();
                break;
        }

        $topics = $query->paginate(20);

        return $this->response->paginator($topics, new TopicTransformer());
    }

    /**
     * 用户的话题列表
     * @param  User    $user    [description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function userIndex(User $user, Request $request)
    {
        $topics = $user->topics()->recent()
            ->paginate(20);

        return $this->response->paginator($topics, new TopicTransformer());
    }

    /**
     * 创建话题
     * @param  TopicRequest $request [description]
     * @param  Topic        $topic   [description]
     * @return [type]                [description]
     */
    public function store(TopicRequest $request, Topic $topic)
    {
        $topic->fill($request->all());
        $topic->user_id = $this->user()->id;
        $topic->save();

        return $this->response->item($topic, new TopicTransformer())
            ->setStatusCode(201);
    }

    /**
     * 修改话题
     * @param  TopicRequest $request [description]
     * @param  Topic        $topic   [description]
     * @return [type]                [description]
     */
    public function update(TopicRequest $request, Topic $topic)
    {
        $this->authorize('update', $topic);

        $topic->update($request->all());
        return $this->response->item($topic, new TopicTransformer());
    }

    /**
     * 删除话题
     * @param  Topic  $topic [description]
     * @return [type]        [description]
     */
    public function destroy(Topic $topic)
    {
        $this->authorize('update', $topic);

        $topic->delete();
        return $this->response->noContent();
    }
}