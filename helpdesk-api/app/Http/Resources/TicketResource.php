<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'anonymous' => $this->anonymous,
            'token' => $this->when($this->anonymous, $this->token),
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'sub_category_id' => $this->sub_category_id,
            'sub_category' => $this->whenLoaded('subCategory', function () {
                return [
                    'id' => $this->subCategory->id,
                    'name' => $this->subCategory->name,
                ];
            }),
            'status' => $this->status,
            'priority' => $this->priority,
            'assigned_to' => $this->assigned_to,
            'assigned_user' => $this->whenLoaded('assignedTo', function () {
                return [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                ];
            }),
            'nim' => $this->nim,
            'nama' => $this->nama,
            'email' => $this->email,
            'prodi' => $this->prodi,
            'semester' => $this->semester,
            'no_hp' => $this->no_hp,
            'read_by_admin' => $this->read_by_admin,
            'read_by_disposisi' => $this->read_by_disposisi,
            'read_by_student' => $this->read_by_student,
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'file_name' => $attachment->file_name,
                        'file_type' => $attachment->file_type,
                        'file_url' => $attachment->file_url,
                    ];
                });
            }),
            'chat_count' => $this->chat_count,
            'has_unread_chat' => $this->has_unread_chat,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
