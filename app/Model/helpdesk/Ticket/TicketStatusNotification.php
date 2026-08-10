<?php

namespace App\Model\helpdesk\Ticket;

use App\BaseModel;

/**
 * Rule: when a ticket sits in $status_id for more than $days days,
 * email everyone listed in $recipients.
 */
class TicketStatusNotification extends BaseModel
{
    protected $table = 'ticket_status_notifications';

    protected $fillable = ['status_id', 'days', 'recipients', 'notify_creator', 'enabled', 'repeat_daily'];

    protected $casts = [
        'enabled'        => 'boolean',
        'repeat_daily'   => 'boolean',
        'notify_creator' => 'boolean',
    ];

    public function status()
    {
        return $this->belongsTo(Ticket_Status::class, 'status_id');
    }

    /**
     * @return array list of clean, non-empty email addresses
     */
    public function recipientList()
    {
        return collect(explode(',', (string) $this->recipients))
            ->map(function ($email) {
                return trim($email);
            })
            ->filter(function ($email) {
                return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);
            })
            ->unique()
            ->values()
            ->all();
    }
}
