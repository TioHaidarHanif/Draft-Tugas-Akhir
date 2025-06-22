<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TicketDetailResource extends TicketResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $data = parent::toArray($request);
        
        $data['histories'] = $this->whenLoaded('histories', function () {
            return $this->histories->map(function ($history) {
                return [
                    'id' => $history->id,
                    'action' => $history->action,
                    'old_status' => $history->old_status,
                    'new_status' => $history->new_status,
                    'old_assigned_to' => $history->old_assigned_to,
                    'new_assigned_to' => $history->new_assigned_to,
                    'comment' => $history->comment,
                    'updated_by' => $history->updated_by,
                    'updater_name' => $history->updater ? $history->updater->name : null,
                    'timestamp' => $history->timestamp,
                ];
            });
        });
        
        $data['feedbacks'] = $this->whenLoaded('feedbacks', function () {
            return $this->feedbacks->map(function ($feedback) {
                return [
                    'id' => $feedback->id,
                    'rating' => $feedback->rating,
                    'comment' => $feedback->comment,
                    'created_at' => $feedback->created_at,
                ];
            });
        });
        
        return $data;
    }
}
