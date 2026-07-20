<?php

namespace App\Http\Controllers\Agent\helpdesk;

// controllers
use App\Exports\TicketExport;
use App\Http\Controllers\Controller;
use App\Model\helpdesk\Manage\Help_topic;
// request
use App\Model\helpdesk\Ticket\Tickets;
// Model
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Vsmoraes\Pdf\PdfFacade;

// classes

/**
 * ReportController
 * This controlleris used to fetch reports in the agent panel.
 *
 * @author      Ladybird <info@ladybirdweb.com>
 */
class ReportController extends Controller
{
    /**
     * Create a new controller instance.
     * constructor to check
     * 1. authentication
     * 2. user roles
     * 3. roles must be agent.
     *
     * @return void
     */
    public function __construct()
    {
        // checking for authentication
        $this->middleware('auth');
        // checking if the role is agent
        $this->middleware('role.agent');
    }

    /**
     * Get the Report page.
     *
     * @return type view
     */
    public function index()
    {
        try {
            return view('themes.default1.agent.helpdesk.report.index');
        } catch (Exception $e) {
        }
    }

    /**
     * function to get help_topic graph.
     *
     * @param type $date111
     * @param type $date122
     * @param type $helptopic
     */
    public function chartdataHelptopic(Request $request, $date111 = '', $date122 = '', $helptopic = '')
    {
        $date11 = strtotime($date122);
        $date12 = strtotime($date111);
        $help_topic = $helptopic;
        $duration = $request->input('duration');
        if ($date11 && $date12 && $help_topic) {
            $date2 = $date12;
            $date1 = $date11;
            $duration = null;
            if ($request->input('open') == null || $request->input('closed') == null || $request->input('reopened') == null || $request->input('overdue') == null || $request->input('deleted') == null) {
                $duration = 'day';
            }
        } else {
            // generating current date
            $date2 = strtotime(date('Y-m-d'));
            $date3 = date('Y-m-d');
            $format = 'Y-m-d';
            // generating a date range of 1 month
            if ($request->input('duration') == 'day') {
                $date1 = strtotime(date($format, strtotime('-15 day'.$date3)));
            } elseif ($request->input('duration') == 'week') {
                $date1 = strtotime(date($format, strtotime('-69 days'.$date3)));
            } elseif ($request->input('duration') == 'month') {
                $date1 = strtotime(date($format, strtotime('-179 days'.$date3)));
            } else {
                $date1 = strtotime(date($format, strtotime('-30 days'.$date3)));
            }
//            $help_topic = Help_topic::where('status', '=', '1')->min('id');
        }

        $return = '';
        $last = '';
        $j = 0;
        $created1 = 0;
        $closed1 = 0;
        $reopened1 = 0;
        $in_progress = \DB::table('tickets')->where('help_topic_id', '=', $help_topic)->where('status', '=', 1)->count();

        for ($i = $date1; $i <= $date2; $i = $i + 86400) {
            $j++;
            $thisDate = date('Y-m-d', $i);
            $thisDate1 = date('jS F', $i);
            $open_array = [];
            $closed_array = [];
            $reopened_array = [];

            if ($request->input('open') || $request->input('closed') || $request->input('reopened')) {
                if ($request->input('open') && $request->input('open') == 'on') {
                    $created = \DB::table('tickets')->select('created_at')->where('help_topic_id', '=', $help_topic)->where('created_at', 'LIKE', '%'.$thisDate.'%')->count();
                    $open_array = ['open' => $created];
                }
                if ($request->input('closed') && $request->input('closed') == 'on') {
                    $closed = \DB::table('tickets')->select('closed_at')->where('help_topic_id', '=', $help_topic)->where('closed_at', 'LIKE', '%'.$thisDate.'%')->count();
                    $closed_array = ['closed' => $closed];
                }
                if ($request->input('reopened') && $request->input('reopened') == 'on') {
                    $reopened = \DB::table('tickets')->select('reopened_at')->where('help_topic_id', '=', $help_topic)->where('reopened_at', 'LIKE', '%'.$thisDate.'%')->count();
                    $reopened_array = ['reopened' => $reopened];
                }
//                if ($request->input('overdue') && $request->input('overdue') == 'on') {
//                    $overdue = Tickets::where('status', '=', 1)->where('isanswered', '=', 0)->where('dept_id', '=', $dept->id)->orderBy('id', 'DESC')->get();
//                }
//                        $open_array = ['open'=>$created1];
//                        $closed_array = ['closed'=>$closed1];
//                        $reopened_array = ['reopened'=>$reopened1];
                $value = ['date' => $thisDate1];
//                        if($open_array) {
                $value = array_merge($value, $open_array);
                $value = array_merge($value, $closed_array);
                $value = array_merge($value, $reopened_array);
                $value = array_merge($value, ['inprogress' => $in_progress]);
//                        } else {
//                            $value = "";
//                        }
                $array = array_map('htmlentities', $value);
                $json = html_entity_decode(json_encode($array));
                $return .= $json.',';
            } else {
                if ($duration == 'week') {
                    $created = \DB::table('tickets')->select('created_at')->where('help_topic_id', '=', $help_topic)->where('created_at', 'LIKE', '%'.$thisDate.'%')->count();
                    $created1 += $created;
                    $closed = \DB::table('tickets')->select('closed_at')->where('help_topic_id', '=', $help_topic)->where('closed_at', 'LIKE', '%'.$thisDate.'%')->count();
                    $closed1 += $closed;
                    $reopened = \DB::table('tickets')->select('reopened_at')->where('help_topic_id', '=', $help_topic)->where('reopened_at', 'LIKE', '%'.$thisDate.'%')->count();
                    $reopened1 += $reopened;
                    if ($j % 7 == 0) {
                        $open_array = ['open' => $created1];
                        $created1 = 0;
                        $closed_array = ['closed' => $closed1];
                        $closed1 = 0;
                        $reopened_array = ['reopened' => $reopened1];
                        $reopened1 = 0;
                        $value = ['date' => $thisDate1];
//                        if($open_array) {
                        $value = array_merge($value, $open_array);
                        $value = array_merge($value, $closed_array);
                        $value = array_merge($value, $reopened_array);
                        $value = array_merge($value, ['inprogress' => $in_progress]);
//                        } else {
//                            $value = "";
//                        }
                        $array = array_map('htmlentities', $value);
                        $json = html_entity_decode(json_encode($array));
                        $return .= $json.',';
                    }
                } elseif ($duration == 'month') {
                    $created_month = \DB::table('tickets')->select('created_at')->where('help_topic_id', '=', $help_topic)->where('created_at', 'LIKE', '%'.$thisDate.'%')->count();
                    $created1 += $created_month;
                    $closed_month = \DB::table('tickets')->select('closed_at')->where('help_topic_id', '=', $help_topic)->where('closed_at', 'LIKE', '%'.$thisDate.'%')->count();
                    $closed1 += $closed_month;
                    $reopened_month = \DB::table('tickets')->select('reopened_at')->where('help_topic_id', '=', $help_topic)->where('reopened_at', 'LIKE', '%'.$thisDate.'%')->count();
                    $reopened1 += $reopened_month;
                    if ($j % 30 == 0) {
                        $open_array = ['open' => $created1];
                        $created1 = 0;
                        $closed_array = ['closed' => $closed1];
                        $closed1 = 0;
                        $reopened_array = ['reopened' => $reopened1];
                        $reopened1 = 0;
                        $value = ['date' => $thisDate1];

                        $value = array_merge($value, $open_array);
                        $value = array_merge($value, $closed_array);
                        $value = array_merge($value, $reopened_array);
                        $value = array_merge($value, ['inprogress' => $in_progress]);

                        $array = array_map('htmlentities', $value);
                        $json = html_entity_decode(json_encode($array));
                        $return .= $json.',';
                    }
                } else {
                    if ($request->input('default') == null) {
                        $help_topic = Help_topic::where('status', '=', '1')->min('id');
                    }
                    $created = \DB::table('tickets')->select('created_at')->where('help_topic_id', '=', $help_topic)->where('created_at', 'LIKE', '%'.$thisDate.'%')->count();
                    $open_array = ['open' => $created];
                    $closed = \DB::table('tickets')->select('closed_at')->where('help_topic_id', '=', $help_topic)->where('closed_at', 'LIKE', '%'.$thisDate.'%')->count();
                    $closed_array = ['closed' => $closed];
                    $reopened = \DB::table('tickets')->select('reopened_at')->where('help_topic_id', '=', $help_topic)->where('reopened_at', 'LIKE', '%'.$thisDate.'%')->count();
                    $reopened_array = ['reopened' => $reopened];
                    if ($j % 1 == 0) {
                        $open_array = ['open' => $created];
                        $created = 0;
                        $closed_array = ['closed' => $closed];
                        $closed = 0;
                        $reopened_array = ['reopened' => $reopened];
                        $reopened = 0;
                        $value = ['date' => $thisDate1];
                        if ($request->input('default') == null) {
                            $value = array_merge($value, $open_array);
                            $value = array_merge($value, $closed_array);
                            $value = array_merge($value, $reopened_array);
                            $value = array_merge($value, ['inprogress' => $in_progress]);
                        } else {
                            if ($duration == null) {
                                if ($request->input('open') == 'on') {
                                    $value = array_merge($value, $open_array);
                                }
                                if ($request->input('closed') == 'on') {
                                    $value = array_merge($value, $closed_array);
                                }
                                if ($request->input('reopened') == 'on') {
                                    $value = array_merge($value, $reopened_array);
                                }
                            } else {
                                $value = array_merge($value, $open_array);
                                $value = array_merge($value, $closed_array);
                                $value = array_merge($value, $reopened_array);
                                $value = array_merge($value, ['inprogress' => $in_progress]);
                            }
                        }

//                        } else {
//                            $value = "";
//                        }
                        $array = array_map('htmlentities', $value);
                        $json = html_entity_decode(json_encode($array));
                        $return .= $json.',';
                    }
                }
            }

//            $value = ['date' => $thisDate];
//            if($open_array) {
//                $value = array_merge($value,$open_array);
//            }
//            if($closed_array) {
//                $value = array_merge($value,$closed_array);
//            }
//            if($reopened_array) {
//                $value = array_merge($value,$reopened_array);
//            }
        }
        $last = rtrim($return, ',');

        return '['.$last.']';
    }

    public function helptopicPdf(Request $request)
    {
        $table_datas = json_decode($request->input('pdf_form'));
        $table_help_topic = json_decode($request->input('pdf_form_help_topic'));
        $html = view('themes.default1.agent.helpdesk.report.pdf', compact('table_datas', 'table_help_topic'))->render();
        $html1 = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        return PdfFacade::load($html1)->show(false, false, false);
    }

    /**
     * Export tickets to Excel filtered by created_at range.
     * Columns: Fecha, Tiempo, Detalle, Proyecto, Ticket, Estado Ticket, Tipo Soporte, Tablero, Agente, Solucion.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    public function exportTickets(Request $request)
    {
        $startInput = $request->query('start_date');
        $endInput = $request->query('end_date');

        $query = DB::table('tickets')
            ->leftJoin('ticket_status', 'tickets.status', '=', 'ticket_status.id')
            ->leftJoin('help_topic', 'tickets.help_topic_id', '=', 'help_topic.id')
            ->leftJoin('users', 'tickets.assigned_to', '=', 'users.id')
            ->select([
                'tickets.id',
                'tickets.created_at',
                'tickets.actual_resolution_hours',
                'tickets.ticket_number',
                DB::raw('(SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR \', \')
                    FROM team_assign_agent taa
                    INNER JOIN teams t ON t.id = taa.team_id
                    WHERE taa.agent_id = tickets.assigned_to) as team_name'),
                'ticket_status.name as status_name',
                'help_topic.topic as help_topic_topic',
                DB::raw("TRIM(CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, ''))) as agent_name"),
                DB::raw("(SELECT title FROM ticket_thread WHERE ticket_id = tickets.id AND poster = 'client' ORDER BY id ASC LIMIT 1) as detalle"),
                DB::raw("(SELECT content FROM ticket_form_data WHERE ticket_id = tickets.id AND (title = 'tablero' OR title = 'Tablero') ORDER BY id ASC LIMIT 1) as tablero"),
                DB::raw("(SELECT body FROM ticket_thread WHERE ticket_id = tickets.id AND poster = 'support' ORDER BY id DESC LIMIT 1) as solucion_body"),
            ])
            ->where('tickets.is_deleted', '=', 0);

        if ($startInput && $endInput) {
            try {
                $start = Carbon::createFromFormat('d/m/Y', $startInput)->startOfDay();
                $end = Carbon::createFromFormat('d/m/Y', $endInput)->endOfDay();
                $query->whereBetween('tickets.created_at', [$start, $end]);
            } catch (Exception $e) {
                $query->whereBetween('tickets.created_at', [
                    Carbon::now()->subDays(30)->startOfDay(),
                    Carbon::now()->endOfDay(),
                ]);
            }
        } else {
            $query->whereBetween('tickets.created_at', [
                Carbon::now()->subDays(30)->startOfDay(),
                Carbon::now()->endOfDay(),
            ]);
        }

        $rows = $query->orderBy('tickets.id', 'desc')->get();

        $data = [];
        foreach ($rows as $row) {
            $fecha = $row->created_at ? Carbon::parse($row->created_at)->format('d/m/Y') : '';

            $tiempo = '';
            if ($row->actual_resolution_hours !== null && $row->actual_resolution_hours !== '') {
                $tiempo = (string) $row->actual_resolution_hours;
            }

            $detalle = $row->detalle ?? '';
            $proyecto = $row->team_name ?? '';
            $ticketNum = $row->ticket_number ?? '';

            $statusName = $row->status_name ?? '';
            $estado = '';
            if ($statusName !== '') {
                $estado = trans('lang.'.strtolower($statusName));
                if ($estado === 'lang.'.strtolower($statusName)) {
                    $estado = $statusName;
                }
            }

            $tipoSoporte = $row->help_topic_topic ?? '';
            $tablero = $row->tablero ?? '';
            $agente = trim((string) ($row->agent_name ?? ''));

            $body = $row->solucion_body;
            if ($body !== null && ! is_string($body)) {
                $body = (string) $body;
            }
            $solucion = '';
            if ($body) {
                $solucion = strip_tags(html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $solucion = preg_replace('/\s+/u', ' ', trim($solucion));
            }

            $data[] = [
                $fecha,
                $tiempo,
                $detalle,
                $proyecto,
                $ticketNum,
                $estado,
                $tipoSoporte,
                $tablero,
                $agente,
                $solucion,
            ];
        }

        return Excel::download(new TicketExport($data), 'tickets.xlsx');
    }
}
