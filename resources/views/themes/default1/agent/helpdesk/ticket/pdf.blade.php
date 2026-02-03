<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Ticket {{ $tickets->ticket_number ?? $id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #000;
        }
        h3 {
            margin: 10px 0;
            color: #333;
            font-size: 14px;
        }
        hr {
            border: none;
            border-top: 1px solid #ccc;
            margin: 20px 0;
        }
        .ticket-info {
            margin-bottom: 30px;
            padding: 10px;
            background-color: #f5f5f5;
        }
        .thread-content {
            margin: 20px 0;
            padding: 10px;
            border-left: 3px solid #007bff;
        }
        p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="ticket-info">
        <h3>Ticket Title: {{ $tickets->title ?? 'N/A' }}</h3>
        <h3>Ticket Number: {{ $tickets->ticket_number ?? $id }}</h3>
        @if(isset($tickets->department) && $tickets->department)
        <h3>Ticket Department: {{ $tickets->department }}</h3>
        @endif
        @if(isset($tickets->helptopic) && $tickets->helptopic)
        <h3>Ticket Helptopic: {{ $tickets->helptopic }}</h3>
        @endif
    </div>
    
    @if($ticket && method_exists($ticket, 'thread'))
        @php
            $threads = $ticket->thread;
        @endphp
        @if($threads && $threads->count() > 0)
            @foreach($threads as $thread)
                @if($thread->body)
                <div class="thread-content">
                    <p><strong>Date:</strong> {{ $thread->created_at ? date('Y-m-d H:i:s', strtotime($thread->created_at)) : '' }}</p>
                    <p>{!! strip_tags($thread->body, '<p><br><strong><em><ul><ol><li><a>') !!}</p>
                </div>
                <hr>
                @endif
            @endforeach
        @else
            <p>No thread content available for this ticket.</p>
        @endif
    @else
        <p>Unable to load thread information.</p>
    @endif
</body>
</html>