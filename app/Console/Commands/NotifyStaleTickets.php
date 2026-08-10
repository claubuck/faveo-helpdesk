<?php

namespace App\Console\Commands;

use App\Http\Controllers\Common\PhpMailController;
use App\Model\helpdesk\Ticket\TicketStatusNotification;
use App\Model\helpdesk\Ticket\Tickets;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Emails a fixed list of addresses when a ticket has been sitting in a given
 * status for longer than the configured number of days.
 *
 * Rules live in the ticket_status_notifications table, one row per
 * status + threshold + recipient list.
 */
class NotifyStaleTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticket:notify-stale {--dry-run : List what would be sent without sending anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify by email about tickets stuck in a status for too long';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dry = (bool) $this->option('dry-run');
        $rules = TicketStatusNotification::where('enabled', 1)->get();

        if ($rules->isEmpty()) {
            $this->info('No enabled rules in ticket_status_notifications, nothing to do.');

            return 0;
        }

        $mailer = new PhpMailController();
        $sent = 0;

        foreach ($rules as $rule) {
            $recipients = $rule->recipientList();
            if (empty($recipients) && !$rule->notify_creator) {
                $this->warn("Rule {$rule->id} has no valid recipients, skipped.");
                continue;
            }

            $cutoff = Carbon::now()->subDays($rule->days);

            $tickets = Tickets::where('status', '=', $rule->status_id)
                    ->where('is_deleted', '=', 0)
                    ->whereNotNull('status_changed_at')
                    ->where('status_changed_at', '<=', $cutoff)
                    ->get();

            foreach ($tickets as $ticket) {
                if ($this->alreadyNotified($rule, $ticket)) {
                    continue;
                }

                if ($dry) {
                    $targets = $recipients;
                    $creator = $this->creatorOf($ticket, $rule, $recipients);
                    if ($creator) {
                        $targets[] = $creator['email'].' (creador)';
                    }
                    $this->line("[dry-run] #{$ticket->ticket_number} ({$rule->days}+ days in status {$rule->status_id}) -> ".implode(', ', $targets));
                    $sent++;
                    continue;
                }

                try {
                    $this->notify($mailer, $rule, $ticket, $recipients);
                    $this->logNotification($rule, $ticket);
                    $sent++;
                } catch (Exception $e) {
                    loging('ticket-notify-stale', 'Ticket '.$ticket->id.': '.$e->getMessage(), 'error');
                    $this->error("Ticket {$ticket->id}: ".$e->getMessage());
                }
            }
        }

        $message = $dry ? "Would notify {$sent} ticket(s)." : "Notified {$sent} ticket(s).";
        $this->info($message);
        if (!$dry && $sent > 0) {
            loging('ticket-notify-stale', $message, 'info');
        }

        return 0;
    }

    /**
     * A ticket is notified once per status change. When repeat_daily is on,
     * it is notified again every day while it stays in that status.
     *
     * @return bool
     */
    protected function alreadyNotified(TicketStatusNotification $rule, Tickets $ticket)
    {
        $log = DB::table('ticket_status_notification_log')
                ->where('rule_id', '=', $rule->id)
                ->where('ticket_id', '=', $ticket->id)
                ->where('status_changed_at', '=', $ticket->status_changed_at)
                ->first();

        if (!$log) {
            return false;
        }

        if ($rule->repeat_daily) {
            return Carbon::parse($log->notified_at)->isSameDay(Carbon::now());
        }

        return true;
    }

    /**
     * The person who opened the ticket, when the rule asks for it and the
     * account still has a usable address.
     *
     * Returns null when they are already covered by the fixed list, so an
     * agent who opened their own ticket does not get two copies.
     *
     * @return array|null ['name' => ..., 'email' => ...]
     */
    protected function creatorOf(Tickets $ticket, TicketStatusNotification $rule, array $recipients = [])
    {
        if (!$rule->notify_creator) {
            return null;
        }

        $user = DB::table('users')->where('id', '=', $ticket->user_id)->first();
        if (!$user || empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $already = array_map('strtolower', $recipients);
        if (in_array(strtolower($user->email), $already, true)) {
            return null;
        }

        $name = trim($user->first_name.' '.$user->last_name);

        return ['name' => $name !== '' ? $name : $user->user_name, 'email' => $user->email];
    }

    /**
     * @return void
     */
    protected function notify(PhpMailController $mailer, TicketStatusNotification $rule, Tickets $ticket, array $recipients)
    {
        $status_name = optional($rule->status)->name ?: $rule->status_id;
        $days = Carbon::parse($ticket->status_changed_at)->diffInDays(Carbon::now());
        $since = Carbon::parse($ticket->status_changed_at)->format('d/m/Y H:i');
        $subject = 'Ticket #'.$ticket->ticket_number.' lleva '.$days.' días en estado '.$status_name;
        $from = $mailer->mailfrom('0', $ticket->dept_id);

        // Staff get the agent view; the ticket owner gets the public link,
        // since the agent thread is not reachable for a client account.
        $staff_link = route('ticket.thread', $ticket->id);
        $staff_body = '<p>El ticket <strong>#'.e($ticket->ticket_number).'</strong> lleva '
              .$days.' días en el estado <strong>'.e($status_name).'</strong>'
              .' (desde el '.$since.').</p>'
              .'<p><a href="'.$staff_link.'">Ver el ticket</a></p>';

        foreach ($recipients as $email) {
            $mailer->sendmail(
                $from,
                ['name' => $email, 'email' => $email],
                ['subject' => $subject, 'body' => $staff_body],
                ['ticket_number' => $ticket->ticket_number, 'ticket_link' => $staff_link]
            );
        }

        $creator = $this->creatorOf($ticket, $rule, $recipients);
        if ($creator) {
            $client_link = url('check_ticket/'.\Crypt::encrypt($ticket->id));
            $client_body = '<p>Hola '.e($creator['name']).',</p>'
                  .'<p>Tu ticket <strong>#'.e($ticket->ticket_number).'</strong> lleva '
                  .$days.' días en el estado <strong>'.e($status_name).'</strong>'
                  .' (desde el '.$since.').</p>'
                  .'<p><a href="'.$client_link.'">Ver el ticket</a></p>';

            $mailer->sendmail(
                $from,
                ['name' => $creator['name'], 'email' => $creator['email']],
                ['subject' => $subject, 'body' => $client_body],
                ['ticket_number' => $ticket->ticket_number, 'ticket_link_with_number' => $client_link]
            );
        }
    }

    /**
     * @return void
     */
    protected function logNotification(TicketStatusNotification $rule, Tickets $ticket)
    {
        DB::table('ticket_status_notification_log')->updateOrInsert(
            [
                'rule_id'           => $rule->id,
                'ticket_id'         => $ticket->id,
                'status_changed_at' => $ticket->status_changed_at,
            ],
            ['notified_at' => Carbon::now()]
        );
    }
}
