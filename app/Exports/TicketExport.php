<?php

namespace App\Exports;

use App\Models\Ticket;
use Illuminate\Support\Collection;

class TicketExport
{
    protected $startDate;
    protected $endDate;
    protected $userId;
    protected $role;

    public function __construct($startDate = null, $endDate = null, $userId = null, $role = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->userId = $userId;
        $this->role = $role;
    }

    /**
     * Get tickets data for export
     * 
     * @return Collection
     */
    public function collection()
    {
        $query = Ticket::query()->with('category');
        
        // Apply date range filter if provided
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('created_at', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('created_at', '<=', $this->endDate);
        }
        
        // Apply role-based filtering
        if ($this->role === 'student' && $this->userId) {
            // Students can only see their own tickets
            $query->where('user_id', $this->userId);
        } elseif ($this->role === 'disposisi' && $this->userId) {
            // Disposisi members can see tickets assigned to them
            $query->where('assigned_to', $this->userId);
        }
        
        $tickets = $query->get();
        
        // Transform the data
        return $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'category' => $ticket->category ? $ticket->category->name : '-',
                'judul' => $ticket->judul,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'submitter' => $ticket->anonymous ? '[Anonymous]' : $ticket->nama,
                'nim' => $ticket->anonymous ? '-' : $ticket->nim,
                'prodi' => $ticket->prodi,
                'semester' => $ticket->semester,
                'phone' => $ticket->anonymous ? '-' : $ticket->no_hp,
                'anonymous' => $ticket->anonymous ? 'Yes' : 'No',
                'assigned_to' => $ticket->assigned_to,
                'created_at' => $ticket->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $ticket->updated_at->format('Y-m-d H:i:s')
            ];
        });
    }

    /**
     * Get headings for the exported file
     * 
     * @return array
     */
    public function headings()
    {
        return [
            'ID',
            'Category',
            'Judul',
            'Status',
            'Priority',
            'Submitter',
            'NIM',
            'Prodi',
            'Semester',
            'Phone',
            'Anonymous',
            'Assigned To',
            'Created At',
            'Updated At'
        ];
    }
}
