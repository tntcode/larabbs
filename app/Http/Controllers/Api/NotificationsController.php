<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Transformers\NotificationTransformer;

class NotificationsController extends Controller
{
    /**
     * 消息通知列表
     * @return [type] [description]
     */
    public function index()
    {
        $notifications = $this->user->notifications()->paginate(20);

        return $this->response->paginator($notifications, new NotificationTransformer());
    }

    /**
     * 未读消息统计
     * @return [type] [description]
     */
    public function stats()
    {
        return $this->response->array([
            'unread_count' => $this->user()->notification_count,
        ]);
    }
}