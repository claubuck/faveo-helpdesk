<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $system->name ?? 'Report' }} - {{ $help_topic_name->topic ?? 'Help Topic Report' }}</title>
    <style type="text/css">
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            padding: 20px 0;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 24px;
        }
        .header-inner { text-align: center; }
        .header img { max-height: 48px; }
        .header .brand {
            font-size: 18px;
            font-weight: 700;
            color: #111;
            text-decoration: none;
        }
        .meta {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 20px;
        }
        .meta-row { margin-bottom: 6px; }
        .meta-row:last-child { margin-bottom: 0; }
        .meta-label {
            font-weight: 600;
            color: #64748b;
            display: inline-block;
            min-width: 100px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 12px 0;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        .data-table thead tr {
            background: #1e293b;
            color: #fff;
        }
        .data-table th {
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
        }
        .data-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .data-table tbody tr:nth-child(even) { background: #f8fafc; }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody td { color: #334155; }
        .summary-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: separate;
            border-spacing: 8px 0;
        }
        .summary-table td {
            padding: 12px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            width: 25%;
            vertical-align: top;
        }
        .summary-label {
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .summary-value { font-size: 14px; font-weight: 700; color: #0f172a; }
        .summary-table td.inprogress .summary-value { color: #d97706; }
        .summary-table td.created .summary-value { color: #2563eb; }
        .summary-table td.reopened .summary-value { color: #ea580c; }
        .summary-table td.closed .summary-value { color: #059669; }
        .footer-note {
            margin-top: 24px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php
        $company = App\Model\helpdesk\Settings\Company::where('id', '=', '1')->first();
        $system = App\Model\helpdesk\Settings\System::where('id', '=', '1')->first();
        $help_topic_name = App\Model\helpdesk\Manage\Help_topic::where('id', '=', $table_help_topic)->first();
        $table_open = 0;
        $table_closed = 0;
        $table_reopened = 0;
        $has_open = false;
        $has_closed = false;
        $has_reopened = false;
    ?>

    <div class="header">
        <div class="header-inner">
            @if($company->use_logo == 1 && $company->logo)
                <img src="{!! public_path().'/uploads/company'.'/'.$company->logo !!}" alt="Logo"/>
            @else
                <span class="brand">{{ $system->name ?? 'SUPPORT CENTER' }}</span>
            @endif
        </div>
    </div>

    <div class="meta">
        <div class="meta-row">
            <span class="meta-label">{{ Lang::get('lang.help_topic') }}:</span>
            <span>{{ $help_topic_name->topic ?? '—' }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">{{ Lang::get('lang.date') }}:</span>
            <span>{{ isset($table_datas[0]) && isset($table_datas[0]->date) ? $table_datas[0]->date : '' }} — {{ count($table_datas) ? (isset(end($table_datas)->date) ? end($table_datas)->date : '') : '' }}</span>
        </div>
    </div>

    <h2 class="section-title">{{ Lang::get('lang.tabular') }}</h2>

    <table class="data-table">
        <thead>
            <tr>
                <th>{{ Lang::get('lang.date') }}</th>
                @if(array_key_exists(1, $table_datas) && isset($table_datas[1]->open))
                    <th>{{ Lang::get('lang.created') }}</th>
                @endif
                @if(array_key_exists(1, $table_datas) && isset($table_datas[1]->closed))
                    <th>{{ Lang::get('lang.closed') }}</th>
                @endif
                @if(array_key_exists(1, $table_datas) && isset($table_datas[1]->reopened))
                    <th>{{ Lang::get('lang.reopened') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($table_datas as $table_data)
                <tr>
                    <td>{{ isset($table_data->date) ? $table_data->date : '—' }}</td>
                    @if(array_key_exists(1, $table_datas) && isset($table_datas[1]->open))
                        <td>
                            @if(isset($table_data->open) && $table_data->open !== '' && $table_data->open !== null)
                                @php $table_open += (int) $table_data->open; $has_open = true; @endphp
                                {{ $table_data->open }}
                            @else
                                —
                            @endif
                        </td>
                    @endif
                    @if(array_key_exists(1, $table_datas) && isset($table_datas[1]->closed))
                        <td>
                            @if(isset($table_data->closed) && $table_data->closed !== '' && $table_data->closed !== null)
                                @php $table_closed += (int) $table_data->closed; $has_closed = true; @endphp
                                {{ $table_data->closed }}
                            @else
                                —
                            @endif
                        </td>
                    @endif
                    @if(array_key_exists(1, $table_datas) && isset($table_datas[1]->reopened))
                        <td>
                            @if(isset($table_data->reopened) && $table_data->reopened !== '' && $table_data->reopened !== null)
                                @php $table_reopened += (int) $table_data->reopened; $has_reopened = true; @endphp
                                {{ $table_data->reopened }}
                            @else
                                —
                            @endif
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            @if(array_key_exists(1, $table_datas) && isset($table_datas[1]->inprogress))
                <td class="inprogress">
                    <div class="summary-label">{{ Lang::get('lang.inprogress') ?? 'In progress' }}</div>
                    <div class="summary-value">{{ $table_datas[1]->inprogress }}</div>
                </td>
            @endif
            @if(!empty($has_open))
                <td class="created">
                    <div class="summary-label">{{ Lang::get('lang.created') }}</div>
                    <div class="summary-value">{{ $table_open }}</div>
                </td>
            @endif
            @if(!empty($has_reopened))
                <td class="reopened">
                    <div class="summary-label">{{ Lang::get('lang.reopened') }}</div>
                    <div class="summary-value">{{ $table_reopened }}</div>
                </td>
            @endif
            @if(!empty($has_closed))
                <td class="closed">
                    <div class="summary-label">{{ Lang::get('lang.closed') }}</div>
                    <div class="summary-value">{{ $table_closed }}</div>
                </td>
            @endif
        </tr>
    </table>

    <p class="footer-note">{{ $system->name ?? 'Support' }} · {{ date('d/m/Y H:i') }}</p>
</body>
</html>
