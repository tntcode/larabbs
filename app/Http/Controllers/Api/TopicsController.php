<?php

namespace App\Http\Controllers\Api;

use App\Models\Topic;
use Illuminate\Http\Request;
use App\Transformers\TopicTransformer;
use App\Http\Requests\Api\TopicRequest;

class TopicsController extends Controller
{
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