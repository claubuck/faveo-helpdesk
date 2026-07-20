<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TicketExport implements FromArray, WithHeadings
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Tiempo',
            'Detalle',
            'Proyecto',
            'Ticket',
            'Estado Ticket',
            'Tipo Soporte',
            'Tablero',
            'Agente',
            'Solucion',
        ];
    }

    public function array(): array
    {
        return $this->data;
    }
}
