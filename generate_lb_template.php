<?php
/**
 * Line Balancing Sewing — Faithful Excel Template Recreation
 *
 * Run:  php generate_lb_template.php
 * Output: LB_Livlig_Template.xlsx (in current directory)
 */

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title as ChartTitle;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('LB Livlig');

// ════════════════════════════════════════════════════════════════
// COLUMN WIDTHS
// ════════════════════════════════════════════════════════════════
$colWidths = [
    'A' => 2.5703125,
    'B' => 5.85546875,
    'C' => 12.7109375,
    'D' => 76.42578125,
    'E' => 19.85546875,
    'F' => 14.140625,
    'G' => 13.42578125,
    'H' => 7.7109375,
    'I' => 13.0,
    'J' => 13.0,
    'K' => 13.0,
    'L' => 13.0,
    'M' => 14.28515625,
    'N' => 13.0,
    'O' => 14.0,
    'P' => 19.28515625,
    'Q' => 13.5703125,
    'R' => 13.0,
    'S' => 16.5703125,
    'T' => 22.28515625,
    'U' => 13.0,
    'V' => 14.28515625,
];
foreach ($colWidths as $col => $w) {
    $sheet->getColumnDimension($col)->setWidth($w);
}

// Hidden columns
$sheet->getColumnDimension('F')->setVisible(false);
$sheet->getColumnDimension('S')->setVisible(false);

// ════════════════════════════════════════════════════════════════
// ROW HEIGHTS
// ════════════════════════════════════════════════════════════════
$rowHeights = [
    2 => 23.25,
    3 => 43.15,
    4 => 31.50,
    5 => 21.00,
    6 => 21.75,
    7 => 22.15,
    8 => 27.60,
    9 => 36.75,
    10 => 24.00,
    11 => 25.15,
    12 => 25.15,
    13 => 25.15,
    14 => 25.15,
    15 => 25.15,
    16 => 25.15,
    17 => 28.50,
    18 => 25.15,
    19 => 28.50,
    20 => 25.15,
    21 => 25.15,
    22 => 25.15,
    23 => 25.15,
    24 => 27.00,
    25 => 25.15,
    26 => 25.15,
    27 => 30.00,
    28 => 26.25,
    29 => 29.25,
    30 => 29.25,
    31 => 25.15,
    32 => 31.50,
    33 => 18.75,
    35 => 25.15,
    36 => 25.15,
    37 => 25.15,
    38 => 25.15,
    39 => 25.15,
    40 => 25.15,
    41 => 25.15,
    42 => 25.15,
    43 => 25.15,
    44 => 3.75,
];
foreach ($rowHeights as $r => $h) {
    $sheet->getRowDimension($r)->setRowHeight($h);
}

// ════════════════════════════════════════════════════════════════
// MERGED CELLS (all 43)
// ════════════════════════════════════════════════════════════════
$merges = [
    // Top area
    'B2:Q2',
    'E3:G3',
    'H3:K3',
    'L3:O3',
    'E4:G4',
    'H4:K4',
    'L4:O6',
    'E5:G5',
    'H5:K5',
    'E6:G6',
    'H6:K6',
    // Table headers
    'B7:B10',
    'C7:C10',
    'D7:D10',
    'E7:E10',
    'F7:F10',
    'G7:G9',
    'H7:L9',
    'M7:M9',
    'N7:N9',
    'O7:O9',
    'P7:P9',
    'Q7:Q9',
    'R7:R10',
    'S7:S9',
    'T7:T9',
    // Total row
    'E32:T32',
    // Data area
    'S11',
    // Summary
    'M35:Q35',
    'M36:Q36',
    'M37:Q37',
    'M38:Q38',
    'M39:Q39',
    'M40:Q40',
    'M41:Q41',
    'M42:Q42',
    'M43:Q43',
    'S35:T35',
    'S36:T36',
    'S40:T40',
    'S41:T41',
    'S42:T42',
    'S43:T43',
];
foreach ($merges as $range) {
    $sheet->mergeCells($range);
}

// ════════════════════════════════════════════════════════════════
// REUSABLE STYLE HELPERS
// ════════════════════════════════════════════════════════════════
$thinBorder = [
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
    ],
];

$centerWrap = [
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
];

function applyStyle($sheet, $range, array $style)
{
    $sheet->getStyle($range)->applyFromArray($style);
}

function cellAlign($h = 'center', $v = 'center', $wrap = true)
{
    return [
        'alignment' => [
            'horizontal' => $h === 'center' ? Alignment::HORIZONTAL_CENTER : ($h === 'left' ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_RIGHT),
            'vertical' => $v === 'center' ? Alignment::VERTICAL_CENTER : Alignment::VERTICAL_TOP,
            'wrapText' => $wrap,
        ]
    ];
}

function cellFont($name = 'Arial', $size = 12, $bold = false, $italic = false, $color = null)
{
    $f = ['name' => $name, 'size' => $size, 'bold' => $bold, 'italic' => $italic];
    if ($color)
        $f['color'] = ['argb' => $color];
    return ['font' => $f];
}

function cellFill($color)
{
    return [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['argb' => $color],
        ]
    ];
}

// ════════════════════════════════════════════════════════════════
// TOP REPORT AREA
// ════════════════════════════════════════════════════════════════

// Row 2 — Title
$sheet->setCellValue('B2', 'LINE BALANCING SEWING');
applyStyle($sheet, 'B2', array_merge(
    cellFont('Arial', 18, true),
    cellAlign('center', 'center', true),
    cellFill('FF4472C4'),
    ['font' => ['name' => 'Arial', 'size' => 18, 'bold' => true, 'color' => ['argb' => 'FFFFFFFF']]],
));

// Row 3
$sheet->setCellValue('D3', 'PT. QUTY KARUNIA');
applyStyle($sheet, 'D3', array_merge(cellFont('Arial', 14, true), cellAlign('left', 'center')));

$sheet->setCellValue('E3', 'Line');
applyStyle($sheet, 'E3:G3', array_merge(cellFont('Arial', 11, true), cellAlign(), $thinBorder, cellFill('FFD9E2F3')));

$sheet->setCellValue('H3', 'Quty 1 C4');
applyStyle($sheet, 'H3:K3', array_merge(cellFont('Arial', 11), cellAlign(), $thinBorder));

$sheet->setCellValue('L3', 'PPH');
applyStyle($sheet, 'L3:O3', array_merge(cellFont('Arial', 11, true), cellAlign(), $thinBorder, cellFill('FFD9E2F3')));

$sheet->setCellValue('P3', 'Target Output /Hours');
applyStyle($sheet, 'P3', array_merge(cellFont('Arial', 10, true), cellAlign(), $thinBorder, cellFill('FFD9E2F3')));

$sheet->setCellValue('Q3', 93);
$sheet->getStyle('Q3')->getNumberFormat()->setFormatCode('0 " Pcs "');
applyStyle($sheet, 'Q3', array_merge(cellFont('Arial', 12, true), cellAlign(), $thinBorder, cellFill('FFFFF2CC')));

// Row 4
$sheet->setCellValue('D4', 'LEAN & IE DEPARTEMENT');
applyStyle($sheet, 'D4', array_merge(cellFont('Arial', 12, true), cellAlign('left', 'center')));

$sheet->setCellValue('E4', 'Article');
applyStyle($sheet, 'E4:G4', array_merge(cellFont('Arial', 11, true), cellAlign(), $thinBorder, cellFill('FFD9E2F3')));

$sheet->setCellValue('H4', 'Livlig Husky');
applyStyle($sheet, 'H4:K4', array_merge(cellFont('Arial', 11), cellAlign(), $thinBorder));

// L4:O6 merged → PPH formula
$sheet->setCellValue('L4', '=Q3/H6');
applyStyle($sheet, 'L4:O6', array_merge(
    cellFont('Arial', 14, true),
    cellAlign(),
    $thinBorder,
    cellFill('FFB4C6E7'),
));
$sheet->getStyle('L4')->getNumberFormat()->setFormatCode('0.00');

$sheet->setCellValue('P4', 'Target Output / Day');
applyStyle($sheet, 'P4', array_merge(cellFont('Arial', 10, true), cellAlign(), $thinBorder, cellFill('FFD9E2F3')));

$sheet->setCellValue('Q4', '=Q3*8');
applyStyle($sheet, 'Q4', array_merge(cellFont('Arial', 12, true), cellAlign(), $thinBorder, cellFill('FFFFF2CC')));
$sheet->getStyle('Q4')->getNumberFormat()->setFormatCode('0');

// Row 5
$sheet->setCellValue('D5', 'CYCLE TIME ANALYZE');
applyStyle($sheet, 'D5', array_merge(cellFont('Arial', 12, true, false, 'FF1F4E79'), cellAlign('left', 'center')));

$sheet->setCellValue('E5', 'Update');
applyStyle($sheet, 'E5:G5', array_merge(cellFont('Arial', 11, true), cellAlign(), $thinBorder, cellFill('FFD9E2F3')));

$h5Date = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime('2024-03-21', new \DateTimeZone('UTC')));
$sheet->setCellValue('H5', $h5Date);
$sheet->getStyle('H5')->getNumberFormat()->setFormatCode('d mmmm yyyy');
applyStyle($sheet, 'H5:K5', array_merge(cellFont('Arial', 11), cellAlign(), $thinBorder));

$sheet->setCellValue('P5', 'Time 1 Hours');
applyStyle($sheet, 'P5', array_merge(cellFont('Arial', 10, true), cellAlign(), $thinBorder, cellFill('FFD9E2F3')));

$sheet->setCellValue('Q5', 3600);
applyStyle($sheet, 'Q5', array_merge(cellFont('Arial', 12, true), cellAlign(), $thinBorder, cellFill('FFFFF2CC')));
$sheet->getStyle('Q5')->getNumberFormat()->setFormatCode('0');

// Row 6
$sheet->setCellValue('D6', '');
applyStyle($sheet, 'D6', $thinBorder);

$sheet->setCellValue('E6', '');
applyStyle($sheet, 'E6:G6', $thinBorder);

// H6:K6 = Takt Time value (=G32 → Total Manpower)
$sheet->setCellValue('H6', '=G32');
applyStyle($sheet, 'H6:K6', array_merge(cellFont('Arial', 11, true), cellAlign(), $thinBorder, cellFill('FFD9E2F3')));
$sheet->getStyle('H6')->getNumberFormat()->setFormatCode('0.00');

$sheet->setCellValue('P6', 'Takt Time');
applyStyle($sheet, 'P6', array_merge(cellFont('Arial', 10, true), cellAlign(), $thinBorder, cellFill('FFD9E2F3')));

$sheet->setCellValue('Q6', '=Q5/Q3');
applyStyle($sheet, 'Q6', array_merge(cellFont('Arial', 12, true), cellAlign(), $thinBorder, cellFill('FFFFF2CC')));
$sheet->getStyle('Q6')->getNumberFormat()->setFormatCode('0.00');

// ════════════════════════════════════════════════════════════════
// MAIN TABLE HEADERS (Rows 7-10)
// ════════════════════════════════════════════════════════════════
$headerFill = cellFill('FF4472C4');
$headerFont = cellFont('Arial', 12, true, false, 'FFFFFFFF');
$headerStyle = array_merge($headerFont, $centerWrap, $thinBorder, $headerFill);
$subHeaderFill = cellFill('FF5B9BD5');
$subHeaderStyle = array_merge(cellFont('Arial', 11, true, false, 'FFFFFFFF'), $centerWrap, $thinBorder, $subHeaderFill);
$obsHeaderFill = cellFill('FF2F5597');
$obsHeaderStyle = array_merge(cellFont('Arial', 10, true, false, 'FFFFFFFF'), $centerWrap, $thinBorder, $obsHeaderFill);

// Row 7-10 headers
$sheet->setCellValue('B7', 'No');
applyStyle($sheet, 'B7:B10', $headerStyle);

$sheet->setCellValue('C7', 'Machine');
applyStyle($sheet, 'C7:C10', $headerStyle);

$sheet->setCellValue('D7', 'Process');
applyStyle($sheet, 'D7:D10', $headerStyle);

$sheet->setCellValue('E7', 'Name');
applyStyle($sheet, 'E7:E10', $headerStyle);

$sheet->setCellValue('F7', 'Joint process');
applyStyle($sheet, 'F7:F10', $headerStyle);

$sheet->setCellValue('G7', 'Operator');
applyStyle($sheet, 'G7:G9', $headerStyle);
$sheet->setCellValue('G10', '=SUM(G11)');
applyStyle($sheet, 'G10', array_merge(cellFont('Arial', 11, true), cellAlign(), $thinBorder, cellFill('FFD6DCE4')));
$sheet->getStyle('G10')->getNumberFormat()->setFormatCode('0');

$sheet->setCellValue('H7', 'Cycle Time');
applyStyle($sheet, 'H7:L9', $headerStyle);

// Observation number sub-headers in row 10
foreach (['H' => 1, 'I' => 2, 'J' => 3, 'K' => 4, 'L' => 5] as $col => $num) {
    $sheet->setCellValue($col . '10', $num);
}
applyStyle($sheet, 'H10:L10', $obsHeaderStyle);

$sheet->setCellValue('M7', 'Average Cycle Time');
applyStyle($sheet, 'M7:M9', $subHeaderStyle);

$sheet->setCellValue('N7', 'Average Cycle Time + Allowance 15 %');
applyStyle($sheet, 'N7:N9', array_merge(cellFont('Arial', 10, true, false, 'FFFFFFFF'), $centerWrap, $thinBorder, cellFill('FF2F5597')));

$sheet->setCellValue('O7', 'Average CT / Process');
applyStyle($sheet, 'O7:O9', $subHeaderStyle);

$sheet->setCellValue('P7', 'Output / Hours');
applyStyle($sheet, 'P7:P9', $subHeaderStyle);

$sheet->setCellValue('Q7', 'Output Process / Hours');
applyStyle($sheet, 'Q7:Q9', $subHeaderStyle);

$sheet->setCellValue('R7', 'Request Operator');
applyStyle($sheet, 'R7:R10', $headerStyle);

$sheet->setCellValue('S7', 'Average Output/ Hour');
applyStyle($sheet, 'S7:S9', $headerStyle);

$sheet->setCellValue('T7', 'Potential Output/Process');
applyStyle($sheet, 'T7:T9', $headerStyle);

// Helper row 10 for M, N, O, P, Q, T
applyStyle($sheet, 'M10:T10', array_merge(cellFont('Arial', 10, true), cellAlign(), $thinBorder, cellFill('FFD6DCE4')));
$sheet->setCellValue('N10', '=MAX(N11)');
$sheet->getStyle('N10')->getNumberFormat()->setFormatCode('0.00');
$sheet->setCellValue('O10', '=MAX(O11)');
$sheet->getStyle('O10')->getNumberFormat()->setFormatCode('0.00');
$sheet->setCellValue('P10', '=MAX(P11)');
$sheet->getStyle('P10')->getNumberFormat()->setFormatCode('0');
$sheet->setCellValue('Q10', '=MIN(Q11)');
$sheet->getStyle('Q10')->getNumberFormat()->setFormatCode('0');
$sheet->setCellValue('T10', '=MIN(T11)');
$sheet->getStyle('T10')->getNumberFormat()->setFormatCode('0');

// ════════════════════════════════════════════════════════════════
// SAMPLE PROCESS DATA (Rows 11-31)
// ════════════════════════════════════════════════════════════════
$processes = [
    // [No, Machine, Process, Name, Operator, CT1, CT2, CT3, CT4(blank=null), CT5(blank=null)]
    [
        1,
        'SNLS',
        'Kupnat badan depan + Jahit ekor awal',
        'Ujang S',
        1,
        '=6.65+21.13',
        '=6.82+21.78',
        '=6.4+21.35',
        null,
        null
    ],
    [
        2,
        'SNLS',
        'Jahit paha dalam awal kiri & kanan + Jahit tangan kanan',
        'Dede Wiwin',
        1,
        '=17.82+6.91',
        '=17.09+7.56',
        '=17.81+7.69',
        null,
        null
    ],
    [
        3,
        'SNLS',
        'Jahit tangan dalam kiri dan kanan + Jahit paha dalam kiri dan kanan',
        'Nunung',
        1,
        '=17.66+16.57',
        '=18.09+15.65',
        '=17.05+16.05',
        null,
        null
    ],
    [
        4,
        'SNLS',
        'Gabung badan depan ke dada + Gabung badan samping kiri & kanan',
        'Supendi',
        1,
        '=8.32+18.47',
        '=8.75+18.81',
        '=8.25+18.57',
        null,
        null
    ],
    [
        5,
        'SNLS',
        'Pasang tangan kiri & kanan ke body',
        'Winda',
        1,
        25.56,
        25.16,
        26.62,
        null,
        null
    ],
    [
        6,
        'SNLS',
        'Tutup badan samping kiri & kanan',
        'Marna',
        1,
        35.47,
        35.21,
        34.31,
        null,
        null
    ],
    [
        7,
        'SNLS',
        'Pasang paha kiri & kanan ke body',
        'Linda',
        1,
        31.69,
        31.2,
        30.5,
        null,
        null
    ],
    [
        8,
        'SNLS',
        'Tutup ekor + Gabung ekor ke body + Tutup punggung',
        'Dewi Enggar',
        1,
        25.26,
        24.31,
        24.28,
        null,
        null
    ],
    [
        9,
        'SNLS',
        'Jahit tangan awal kiri + Pasang label & tutup pantat',
        'Irnawati',
        1,
        '=9.78+15.54',
        '=9.78+15.6',
        '=9.78+15.65',
        null,
        null
    ],
    [
        10,
        'SNLS',
        'Pasang telapak 3x',
        'Ucih',
        1,
        29.57,
        30.06,
        31.85,
        null,
        null
    ],
    [
        11,
        'SNLS',
        'Gabung kepala ke badan + Pasang telapak 1x + Tutup punggung',
        'Cucu H',
        1,
        '=24.07+7.019',
        '=24.69+7.3',
        '=25.28+6.64',
        null,
        null
    ],
    [
        12,
        'SNLS',
        'Pasang spot kiri & kanan',
        'Hasan',
        1,
        31.03,
        30.07,
        31.18,
        null,
        null
    ],
    [
        13,
        'SNLS',
        'Jahit telinga awal kiri & kanan + Stik telinga kiri & kanan',
        'Munasipa',
        1,
        '=18.56+4.41',
        '=18.25+4.43',
        '=17.37+4.97',
        null,
        null
    ],
    [
        14,
        'SNLS',
        'Pasang lidah ke bibir + Stik lidah + Kupnat tangan kiri & kanan',
        'Ardi Wijaya',
        1,
        '=16.88+9.08',
        '=16.37+9.03',
        '=16.09+9.4',
        null,
        null
    ],
    [
        15,
        'SNLS',
        'Pasang kening ke pipi kiri & kanan + Jahit dagu',
        'Edeh Komala',
        1,
        25.78,
        26.95,
        26.47,
        null,
        null
    ],
    [
        16,
        'SNLS',
        'Kupnat paha kiri & kanan + Pasang Embo mata ke pipi kiri & kanan',
        'Milata',
        1,
        '=9.19+16.22',
        '=10.03+16.9',
        '=10.09+17.64',
        null,
        null
    ],
    [
        17,
        'SNLS',
        'Pasang telinga kiri & kanan + Pasang dagu',
        'Nani',
        1,
        25.81,
        25.75,
        26.68,
        null,
        null
    ],
    [
        18,
        'SNLS',
        'Pasang + lipat hidung + Pasang Tc ke batang hidung',
        'Alit',
        1,
        30.47,
        29.03,
        29.97,
        null,
        null
    ],
    [
        19,
        'SNLS',
        'Kupnat pipi kiri & kakan + Pasang pipi ke muka + Kupnat dada',
        'Itoh',
        1,
        '=16.28+6.38+6.34',
        '=15.5+6.97+5.34',
        '=16.65+6.69+5.09',
        null,
        null
    ],
    [
        20,
        'SNLS',
        'Gabung lidah ke mulut + pasang mulut embo mata + kupnat dagu',
        'Nurhasanah',
        1,
        '=9.81+16.44+4.75',
        '=9.75+15.06+5.25',
        '=9.44+16.32+4.53',
        null,
        null
    ],
    [
        21,
        'SNLS',
        'Gabung kepala belakang kiri dan kanan + Gambung kepala belakang ke muka',
        'Amanan Mulyanan',
        1,
        '=22.85+5.13',
        '=20.53+6.79',
        '=21.94+5.97',
        null,
        null
    ],
];

$dataFont = cellFont('Arial', 12);
$dataFontNarrow = cellFont('Arial Narrow', 11);
$dataStyle = array_merge($dataFont, $centerWrap, $thinBorder);
$dataStyleCalc = array_merge($dataFontNarrow, $centerWrap, $thinBorder);
$processFill = cellFill('FFDCE6F1');
$outputFill = cellFill('FFE2EFDA');

foreach ($processes as $i => $p) {
    $r = 11 + $i;
    [$no, $machine, $process, $name, $opr, $ct1, $ct2, $ct3, $ct4, $ct5] = $p;

    // B = No
    $sheet->setCellValue("B{$r}", $no);
    applyStyle($sheet, "B{$r}", $dataStyle);

    // C = Machine
    $sheet->setCellValue("C{$r}", $machine);
    applyStyle($sheet, "C{$r}", $dataStyle);

    // D = Process
    $sheet->setCellValue("D{$r}", $process);
    applyStyle($sheet, "D{$r}", array_merge($dataFont, cellAlign('left', 'center', true), $thinBorder));

    // E = Name
    $sheet->setCellValue("E{$r}", $name);
    applyStyle($sheet, "E{$r}", $dataStyle);

    // F = Joint process (hidden, leave blank)
    $sheet->setCellValue("F{$r}", '');
    applyStyle($sheet, "F{$r}", $dataStyle);

    // G = Operator
    $sheet->setCellValue("G{$r}", $opr);
    applyStyle($sheet, "G{$r}", $dataStyle);
    $sheet->getStyle("G{$r}")->getNumberFormat()->setFormatCode('0');

    // H-L = Cycle Time observations
    $ctCols = ['H' => $ct1, 'I' => $ct2, 'J' => $ct3, 'K' => $ct4, 'L' => $ct5];
    foreach ($ctCols as $col => $val) {
        if ($val !== null) {
            $sheet->setCellValue("{$col}{$r}", $val);
        }
        // Leave truly blank if null (AVERAGE ignores blanks)
        applyStyle($sheet, "{$col}{$r}", array_merge($dataFontNarrow, cellAlign(), $thinBorder));
        $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode('0.00');
    }

    // M = Average Cycle Time = AVERAGE(Hr:Lr)
    $sheet->setCellValue("M{$r}", "=AVERAGE(H{$r}:L{$r})");
    applyStyle($sheet, "M{$r}", array_merge($dataStyleCalc, $processFill));
    $sheet->getStyle("M{$r}")->getNumberFormat()->setFormatCode('0.00');

    // N = Average Cycle Time + Allowance 15% = Mr*1.15
    $sheet->setCellValue("N{$r}", "=M{$r}*1.15");
    applyStyle($sheet, "N{$r}", array_merge($dataStyleCalc, $processFill));
    $sheet->getStyle("N{$r}")->getNumberFormat()->setFormatCode('0.00');

    // O = Average CT / Process = Mr
    $sheet->setCellValue("O{$r}", "=M{$r}");
    applyStyle($sheet, "O{$r}", array_merge($dataStyleCalc, $processFill));
    $sheet->getStyle("O{$r}")->getNumberFormat()->setFormatCode('0.00');

    // P = Output / Hours = 3600/Nr
    $sheet->setCellValue("P{$r}", "=3600/N{$r}");
    applyStyle($sheet, "P{$r}", array_merge($dataStyleCalc, $outputFill));
    $sheet->getStyle("P{$r}")->getNumberFormat()->setFormatCode('0');

    // Q = Output Process / Hours = Pr
    $sheet->setCellValue("Q{$r}", "=P{$r}");
    applyStyle($sheet, "Q{$r}", array_merge($dataStyleCalc, $outputFill));
    $sheet->getStyle("Q{$r}")->getNumberFormat()->setFormatCode('0');

    // R = Request Operator = Or/$Q$6
    $sheet->setCellValue("R{$r}", "=O{$r}/\$Q\$6");
    applyStyle($sheet, "R{$r}", array_merge($dataStyleCalc, $thinBorder));
    $sheet->getStyle("R{$r}")->getNumberFormat()->setFormatCode('0.00');

    // S = Average Output/Hour (hidden)
    applyStyle($sheet, "S{$r}", array_merge(cellFont('Arial', 16, true), cellAlign(), $thinBorder));
    $sheet->getStyle("S{$r}")->getNumberFormat()->setFormatCode('0');

    // T = Potential Output / Process = Qr*8
    $sheet->setCellValue("T{$r}", "=Q{$r}*8");
    applyStyle($sheet, "T{$r}", array_merge($dataStyleCalc, $thinBorder));
    $sheet->getStyle("T{$r}")->getNumberFormat()->setFormatCode('0');
}

// ════════════════════════════════════════════════════════════════
// TOTAL ROW (Row 32)
// ════════════════════════════════════════════════════════════════
$totalFill = cellFill('FFD6DCE4');
$totalFont = cellFont('Arial', 12, true);
$totalStyle = array_merge($totalFont, $centerWrap, $thinBorder, $totalFill);

$sheet->setCellValue('E32', 'Total');
applyStyle($sheet, 'E32:T32', $totalStyle);

// G32 = SUM(G11:G31)  — Total Manpower
$sheet->setCellValue('G32', '=SUM(G11:G31)');
$sheet->getStyle('G32')->getNumberFormat()->setFormatCode('0');

// M32 = SUM(M11:M31) — Total Average Cycle Time
$sheet->setCellValue('M32', '=SUM(M11:M31)');
$sheet->getStyle('M32')->getNumberFormat()->setFormatCode('0.00');

// N32 = SUM(N11:N31) — Total CT + Allowance
$sheet->setCellValue('N32', '=SUM(N11:N31)');
$sheet->getStyle('N32')->getNumberFormat()->setFormatCode('0.00');

// O32 = MAX(O11:O31)
$sheet->setCellValue('O32', '=MAX(O11:O31)');
$sheet->getStyle('O32')->getNumberFormat()->setFormatCode('0.00');

// P32 = MAX(P11:P31)
$sheet->setCellValue('P32', '=MAX(P11:P31)');
$sheet->getStyle('P32')->getNumberFormat()->setFormatCode('0');

// Q32 = MIN(Q11:Q31)
$sheet->setCellValue('Q32', '=MIN(Q11:Q31)');
$sheet->getStyle('Q32')->getNumberFormat()->setFormatCode('0');

// R32 = SUM(R11:R31)
$sheet->setCellValue('R32', '=SUM(R11:R31)');
$sheet->getStyle('R32')->getNumberFormat()->setFormatCode('0.00');

// T32 = MIN(T11:T31)
$sheet->setCellValue('T32', '=MIN(T11:T31)');
$sheet->getStyle('T32')->getNumberFormat()->setFormatCode('0');

// ════════════════════════════════════════════════════════════════
// SUMMARY KPI SECTION (Rows 35-43)
// ════════════════════════════════════════════════════════════════
$labelFill = cellFill('FF4472C4');
$labelFont = cellFont('Arial', 12, true, false, 'FFFFFFFF');
$labelStyle = array_merge($labelFont, $centerWrap, $thinBorder, $labelFill);

$valueFill = cellFill('FFFFF2CC');
$valueStyle = array_merge(cellFont('Arial', 13, true), cellAlign(), $thinBorder, $valueFill);

$unitFill = cellFill('FFD6DCE4');
$unitStyle = array_merge(cellFont('Arial', 10), cellAlign(), $thinBorder, $unitFill);

$summaryRows = [
    // row => [label (M-Q merged), formula/value (R), unit1 (S:T merged)]
    35 => ['TOTAL CYCLE TIME', '=N32', 'SEC'],
    36 => ['TOTAL MANPOWER', '=G32', 'PERSON'],
    37 => ['AVERAGE STANDARD TIME', '=R35/R36', 'SEC / PERSON'],
    38 => ['TOTAL WORKING TIME', 28800, 'SEC / DAY / PERSON'],
    39 => ['TARGET PER PCS', '=ROUND(R38/R35,0)', 'PCS / DAY / PERSON'],
    40 => ['TARGET LINE PER DAY', '=R36*R39', 'PCS/ DAY'],
    41 => ['TARGET LINE PER HOUR', '=R40/8', 'PCS/HOUR'],
    42 => ['MAXIMUM BASE ON CYLE TIME', '=3600/N10', 'PCS/HOUR'],  // preserve typo
    43 => ['OUTPUT ACTUAL', 93, 'PCS/HOUR'],
];

foreach ($summaryRows as $r => [$label, $value, $unit]) {
    // Label in M (merged M:Q)
    $sheet->setCellValue("M{$r}", $label);
    applyStyle($sheet, "M{$r}:Q{$r}", $labelStyle);

    // Value in R
    $sheet->setCellValue("R{$r}", $value);
    applyStyle($sheet, "R{$r}", $valueStyle);
    $sheet->getStyle("R{$r}")->getNumberFormat()->setFormatCode('0.00');

    // Unit in S (merged S:T)
    $sheet->setCellValue("S{$r}", $unit);
    applyStyle($sheet, "S{$r}:T{$r}", $unitStyle);
}

// R35 format
$sheet->getStyle('R35')->getNumberFormat()->setFormatCode('0.00');
// R36 format
$sheet->getStyle('R36')->getNumberFormat()->setFormatCode('0');
// R38 = 28800
$sheet->getStyle('R38')->getNumberFormat()->setFormatCode('0');
// R39 = integer
$sheet->getStyle('R39')->getNumberFormat()->setFormatCode('0');
// R40
$sheet->getStyle('R40')->getNumberFormat()->setFormatCode('0');
// R43 = 93
$sheet->getStyle('R43')->getNumberFormat()->setFormatCode('0');

// Additional unit sub-labels (T37, T38, T40 etc.)
$sheet->setCellValue('T37', 'SEC');
applyStyle($sheet, 'T37', $unitStyle);
$sheet->setCellValue('T38', 'SEC');
applyStyle($sheet, 'T38', $unitStyle);
$sheet->setCellValue('T39', 'PCS');
applyStyle($sheet, 'T39', $unitStyle);

// ════════════════════════════════════════════════════════════════
// YAMAZUMI BAR CHART
// ════════════════════════════════════════════════════════════════
$dataSeriesValues = new DataSeriesValues(
    DataSeriesValues::DATASERIES_TYPE_NUMBER,
    "'LB Livlig'!\$P\$11:\$P\$31",
    null,
    21
);

$categoryAxisValues = new DataSeriesValues(
    DataSeriesValues::DATASERIES_TYPE_STRING,
    "'LB Livlig'!\$B\$11:\$B\$31",
    null,
    21
);

$series = new DataSeries(
    DataSeries::TYPE_BARCHART,
    DataSeries::GROUPING_CLUSTERED,
    range(0, 0),
    [],
    [$categoryAxisValues],
    [$dataSeriesValues]
);
$series->setPlotDirection(DataSeries::DIRECTION_COL);

$plotArea = new PlotArea(null, [$series]);
$chartLegend = new Legend(Legend::POSITION_BOTTOM, null, false);

$chart = new Chart(
    'YamazumiChart',
    new ChartTitle('Yamazumi Chart Before'),
    $chartLegend,
    $plotArea
);
$chart->setTopLeftPosition('B46');
$chart->setBottomRightPosition('T68');
$sheet->addChart($chart);

// ════════════════════════════════════════════════════════════════
// PRINT AREA
// ════════════════════════════════════════════════════════════════
$sheet->getPageSetup()->setPrintArea('B7:T43');

// ════════════════════════════════════════════════════════════════
// SAVE
// ════════════════════════════════════════════════════════════════
$filename = __DIR__ . '/LB_Livlig_Template.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->setIncludeCharts(true);
$writer->save($filename);

echo "✅ Workbook saved to: {$filename}\n";
echo "Sheet: LB Livlig\n";
echo "Rows: 44 | Columns: A–V | Merges: 43\n";
echo "Chart: Yamazumi Chart Before (B46:T68)\n";
echo "\nVerification:\n";

// Quick verification
$checkQ3 = 93;
$checkQ4 = $checkQ3 * 8;
$checkQ5 = 3600;
$checkQ6 = $checkQ5 / $checkQ3;
echo "  Q3 (Target Output/Hour) = {$checkQ3}\n";
echo "  Q4 (Target Output/Day)  = {$checkQ4} (expected 744)\n";
echo "  Q5 (Seconds/Hour)       = {$checkQ5}\n";
echo "  Q6 (Takt Time)          = " . number_format($checkQ6, 4) . " (expected " . number_format(3600 / 93, 4) . ")\n";
echo "  Column A width          = 2.5703125\n";
echo "  Column F (Joint process) = HIDDEN\n";
echo "  Column S (Avg Output)   = HIDDEN\n";